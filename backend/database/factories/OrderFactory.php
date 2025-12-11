<?php

namespace Database\Factories;

use App\Enums\OrderSide;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(5, 0.01, 1);
        $price = fake()->randomFloat(2, 500, 100000);

        return [
            'user_id' => User::factory(),
            'symbol' => fake()->randomElement(['BTC', 'ETH']),
            'side' => OrderSide::BUY,
            'price' => $price,
            'amount' => $amount,
            'status' => OrderStatus::OPEN,
            'reserved_value' => $amount * $price * 1.015,
        ];
    }

    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'side' => OrderSide::SELL,
            'reserved_value' => 0,
        ]);
    }

    public function filled(): static
    {
        return $this->state([
            'status' => OrderStatus::FILLED,
            'filled_at' => now(),
        ]);
    }
}
