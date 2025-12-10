<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\Order;
use App\Models\User;
use App\Services\Concerns\LocksRows;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    use LocksRows;

    private const FEE_RATE = 0.015;

    public function __construct(private readonly OrderMatchingService $matcher)
    {
    }

    public function place(User $user, array $payload): Order
    {
        $payload['symbol'] = strtoupper($payload['symbol']);

        /** @var Order $order */
        $order = DB::transaction(function () use ($user, $payload) {
            $amount = BigDecimal::of($payload['amount'])->toScale(8, RoundingMode::HALF_UP);
            $price = BigDecimal::of($payload['price'])->toScale(2, RoundingMode::HALF_UP);
            $limitValue = $amount->multipliedBy($price)->toScale(2, RoundingMode::HALF_UP);

            if ($payload['side'] === OrderSide::BUY->value) {
                return $this->placeBuyOrder($user, $payload, $amount, $limitValue);
            }

            return $this->placeSellOrder($user, $payload, $amount);
        });

        $this->matcher->attemptMatch($order);

        return $order->fresh();
    }

    public function cancel(Order $order): void
    {
        DB::transaction(function () use ($order) {
            /** @var Order $lockedOrder */
            $lockedOrder = $this->lock(Order::query()->whereKey($order->id))->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::OPEN) {
                throw ValidationException::withMessages([
                    'order' => 'Only open orders may be cancelled.',
                ]);
            }

            if ($lockedOrder->side === OrderSide::BUY) {
                $buyer = $this->lock(User::query()->whereKey($lockedOrder->user_id))->firstOrFail();
                $buyer->balance = (string) BigDecimal::of($buyer->balance)
                    ->plus($lockedOrder->reserved_value)
                    ->toScale(2, RoundingMode::HALF_UP);
                $buyer->save();
            } else {
                $asset = $this->lock(
                    Asset::query()
                        ->where('user_id', $lockedOrder->user_id)
                        ->where('symbol', $lockedOrder->symbol)
                )->first();

                if ($asset) {
                    $asset->amount = (string) BigDecimal::of($asset->amount)
                        ->plus($lockedOrder->amount)
                        ->toScale(8, RoundingMode::HALF_UP);
                    $asset->locked_amount = (string) BigDecimal::of($asset->locked_amount)
                        ->minus($lockedOrder->amount)
                        ->toScale(8, RoundingMode::HALF_UP);
                    $asset->save();
                }
            }

            $lockedOrder->status = OrderStatus::CANCELLED;
            $lockedOrder->save();
        });
    }

    private function placeBuyOrder(User $user, array $payload, BigDecimal $amount, BigDecimal $limitValue): Order
    {
        /** @var User $lockedUser */
        $lockedUser = $this->lock(User::query()->whereKey($user->id))->firstOrFail();
        $feeBuffer = $limitValue->multipliedBy(self::FEE_RATE)->toScale(2, RoundingMode::HALF_UP);
        $reservation = $limitValue->plus($feeBuffer);

        if (BigDecimal::of($lockedUser->balance)->isLessThan($reservation)) {
            throw ValidationException::withMessages([
                'balance' => 'Insufficient USD balance.',
            ]);
        }

        $lockedUser->balance = (string) BigDecimal::of($lockedUser->balance)
            ->minus($reservation)
            ->toScale(2, RoundingMode::HALF_UP);
        $lockedUser->save();

        return $lockedUser->orders()->create([
            'symbol' => $payload['symbol'],
            'side' => OrderSide::BUY,
            'price' => $payload['price'],
            'amount' => (string) $amount,
            'status' => OrderStatus::OPEN,
            'reserved_value' => (string) $reservation,
        ]);
    }

    private function placeSellOrder(User $user, array $payload, BigDecimal $amount): Order
    {
        /** @var Asset|null $asset */
        $asset = $this->lock(
            Asset::query()
                ->where('user_id', $user->id)
                ->where('symbol', $payload['symbol'])
        )->first();

        if (! $asset) {
            throw ValidationException::withMessages([
                'asset' => 'You have no holdings for this symbol.',
            ]);
        }

        if (BigDecimal::of($asset->amount)->isLessThan($amount)) {
            throw ValidationException::withMessages([
                'asset' => 'Insufficient asset balance.',
            ]);
        }

        $asset->amount = (string) BigDecimal::of($asset->amount)
            ->minus($amount)
            ->toScale(8, RoundingMode::HALF_UP);
        $asset->locked_amount = (string) BigDecimal::of($asset->locked_amount)
            ->plus($amount)
            ->toScale(8, RoundingMode::HALF_UP);
        $asset->save();

        return $user->orders()->create([
            'symbol' => $payload['symbol'],
            'side' => OrderSide::SELL,
            'price' => $payload['price'],
            'amount' => (string) $amount,
            'status' => OrderStatus::OPEN,
            'reserved_value' => 0,
        ]);
    }
}
