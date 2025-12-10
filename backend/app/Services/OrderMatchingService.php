<?php

namespace App\Services;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Events\OrderMatched;
use App\Http\Resources\ProfileResource;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use App\Services\Concerns\LocksRows;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;

class OrderMatchingService
{
    use LocksRows;

    private const FEE_RATE = 0.015;

    public function attemptMatch(Order $order): void
    {
        $trade = DB::transaction(function () use ($order) {
            /** @var Order|null $freshOrder */
            $freshOrder = $this->lock(
                Order::query()->whereKey($order->id)
            )->first();

            if (! $freshOrder || $freshOrder->status !== OrderStatus::OPEN) {
                return null;
            }

            $counterOrder = $this->findCounterOrder($freshOrder);

            if (! $counterOrder) {
                return null;
            }

            return $this->executeTrade($freshOrder, $counterOrder);
        });

        if (! $trade) {
            return;
        }

        $trade->load([
            'buyOrder.user.assets',
            'sellOrder.user.assets',
        ]);

        $profiles = [
            $trade->buyOrder->user_id => ProfileResource::make($trade->buyOrder->user)->resolve(),
            $trade->sellOrder->user_id => ProfileResource::make($trade->sellOrder->user)->resolve(),
        ];

        OrderMatched::dispatch($trade, $profiles);
    }

    private function findCounterOrder(Order $order): ?Order
    {
        $query = Order::query()
            ->where('symbol', $order->symbol)
            ->where('status', OrderStatus::OPEN)
            ->where('side', $order->side === OrderSide::BUY ? OrderSide::SELL : OrderSide::BUY)
            ->whereKeyNot($order->id);

        if ($order->side === OrderSide::BUY) {
            $query->where('price', '<=', $order->price)
                ->orderBy('price')
                ->orderBy('created_at');
        } else {
            $query->where('price', '>=', $order->price)
                ->orderByDesc('price')
                ->orderBy('created_at');
        }

        /** @var Order|null $counter */
        $counter = $this->lock($query)->first();

        if (! $counter) {
            return null;
        }

        return $this->amountsMatch($order->amount, $counter->amount) ? $counter : null;
    }

    private function executeTrade(Order $order, Order $counter): ?Trade
    {
        $buyOrder = $order->side === OrderSide::BUY ? $order : $counter;
        $sellOrder = $order->side === OrderSide::SELL ? $order : $counter;

        $amount = BigDecimal::of($buyOrder->amount)->toScale(8, RoundingMode::HALF_UP);
        $executedPrice = BigDecimal::of($sellOrder->price)->toScale(2, RoundingMode::HALF_UP);
        $totalValue = $amount->multipliedBy($executedPrice)->toScale(2, RoundingMode::HALF_UP);
        $fee = $totalValue->multipliedBy(self::FEE_RATE)->toScale(2, RoundingMode::HALF_UP);

        /** @var User $buyer */
        $buyer = $this->lock(User::query()->whereKey($buyOrder->user_id))->firstOrFail();
        /** @var User $seller */
        $seller = $this->lock(User::query()->whereKey($sellOrder->user_id))->firstOrFail();

        $buyerAsset = $this->lockOrCreateAsset($buyer->id, $buyOrder->symbol);
        $sellerAsset = $this->lockOrCreateAsset($seller->id, $sellOrder->symbol);

        $refund = BigDecimal::of($buyOrder->reserved_value)
            ->minus($totalValue)
            ->minus($fee);

        if ($refund->isNegative()) {
            $refund = BigDecimal::zero();
        }

        $refund = $refund->toScale(2, RoundingMode::HALF_UP);

        $buyer->balance = (string) BigDecimal::of($buyer->balance)
            ->plus($refund)
            ->toScale(2, RoundingMode::HALF_UP);
        $buyerAsset->amount = (string) BigDecimal::of($buyerAsset->amount)
            ->plus($amount)
            ->toScale(8, RoundingMode::HALF_UP);
        $buyerAsset->save();
        $buyer->save();

        $seller->balance = (string) BigDecimal::of($seller->balance)
            ->plus($totalValue)
            ->toScale(2, RoundingMode::HALF_UP);
        $sellerAsset->locked_amount = (string) BigDecimal::of($sellerAsset->locked_amount)
            ->minus($amount)
            ->toScale(8, RoundingMode::HALF_UP);
        $sellerAsset->save();
        $seller->save();

        $buyOrder->status = OrderStatus::FILLED;
        $buyOrder->filled_at = now();
        $buyOrder->reserved_value = (string) $totalValue->plus($fee)->toScale(2, RoundingMode::HALF_UP);
        $buyOrder->save();

        $sellOrder->status = OrderStatus::FILLED;
        $sellOrder->filled_at = now();
        $sellOrder->save();

        return Trade::create([
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'symbol' => $order->symbol,
            'price' => (string) $executedPrice,
            'amount' => (string) $amount,
            'total_value' => (string) $totalValue,
            'fee' => (string) $fee,
            'executed_at' => now(),
        ]);
    }

    private function lockOrCreateAsset(int $userId, string $symbol): Asset
    {
        $symbol = strtoupper($symbol);

        /** @var Asset|null $asset */
        $asset = $this->lock(
            Asset::query()
                ->where('user_id', $userId)
                ->where('symbol', $symbol)
        )->first();

        if ($asset) {
            return $asset;
        }

        $created = Asset::query()->create([
            'user_id' => $userId,
            'symbol' => $symbol,
            'amount' => 0,
            'locked_amount' => 0,
        ]);

        return $this->lock(Asset::query()->whereKey($created->id))->firstOrFail();
    }

    private function amountsMatch(string $first, string $second): bool
    {
        return BigDecimal::of($first)->isEqualTo(BigDecimal::of($second));
    }
}
