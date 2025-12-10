<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\OrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        if ($request->boolean('mine')) {
            $ordersQuery = $request->user()->orders()->latest();

            if ($symbol = $request->string('symbol')->toString()) {
                $ordersQuery->where('symbol', strtoupper($symbol));
            }

            if ($side = $request->string('side')->toString()) {
                $ordersQuery->where('side', strtolower($side));
            }

            if ($status = $request->string('status')->toString()) {
                $statusEnum = $this->resolveStatus($status);

                if ($statusEnum) {
                    $ordersQuery->where('status', $statusEnum);
                }
            }

            $orders = $ordersQuery->get();

            return response()->json([
                'orders' => OrderResource::collection($orders),
            ]);
        }

        $data = $request->validate([
            'symbol' => ['required', 'string', 'max:10'],
        ]);

        $buyOrders = Order::query()
            ->where('symbol', strtoupper($data['symbol']))
            ->open()
            ->where('side', 'buy')
            ->orderByDesc('price')
            ->orderBy('created_at')
            ->get();

        $sellOrders = Order::query()
            ->where('symbol', strtoupper($data['symbol']))
            ->open()
            ->where('side', 'sell')
            ->orderBy('price')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'symbol' => strtoupper($data['symbol']),
            'buy' => OrderResource::collection($buyOrders),
            'sell' => OrderResource::collection($sellOrders),
        ]);
    }

    public function store(OrderRequest $request): JsonResponse
    {
        $order = $this->orders->place(
            $request->user(),
            $request->validated(),
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    public function cancel(Request $request, Order $order): OrderResource
    {
        abort_unless($order->user_id === $request->user()->id, 403, 'You can only cancel your own orders.');

        $this->orders->cancel($order);

        return new OrderResource($order->fresh());
    }

    private function resolveStatus(string $value): ?OrderStatus
    {
        if (is_numeric($value)) {
            return OrderStatus::tryFrom((int) $value);
        }

        return match (strtolower($value)) {
            'open' => OrderStatus::OPEN,
            'filled' => OrderStatus::FILLED,
            'cancelled', 'canceled' => OrderStatus::CANCELLED,
            default => null,
        };
    }
}
