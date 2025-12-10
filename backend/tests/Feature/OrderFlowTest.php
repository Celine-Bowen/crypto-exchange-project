<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_endpoint_returns_wallet_state(): void
    {
        $user = User::factory()->create(['balance' => 1234.56]);

        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 0.5,
            'locked_amount' => 0.1,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('data.balance', 1234.56)
            ->assertJsonPath('data.assets.0.symbol', 'BTC');
    }

    public function test_buy_order_locks_and_releases_funds_on_cancel(): void
    {
        $user = User::factory()->create(['balance' => 10000]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 1000,
            'amount' => 1,
        ])->assertCreated();

        $orderId = $response['data']['id'];

        $this->assertSame('8985.00', number_format((float) $user->fresh()->balance, 2, '.', ''));

        $this->postJson("/api/orders/{$orderId}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', OrderStatus::CANCELLED->name);

        $this->assertSame('10000.00', number_format((float) $user->fresh()->balance, 2, '.', ''));
    }

    public function test_matching_executes_trade_and_updates_balances(): void
    {
        $buyer = User::factory()->create(['balance' => 200000]);
        $seller = User::factory()->create(['balance' => 0]);

        Asset::factory()->create([
            'user_id' => $seller->id,
            'symbol' => 'BTC',
            'amount' => 1,
            'locked_amount' => 0,
        ]);

        Sanctum::actingAs($seller);

        $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'sell',
            'price' => 95000,
            'amount' => 1,
        ])->assertCreated();

        Sanctum::actingAs($buyer);

        $buyResponse = $this->postJson('/api/orders', [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => 96000,
            'amount' => 1,
        ])->assertCreated();

        $buyOrderId = $buyResponse['data']['id'];

        $this->assertDatabaseHas('orders', [
            'id' => $buyOrderId,
            'status' => OrderStatus::FILLED->value,
        ]);

        $this->assertSame('103575.00', number_format((float) $buyer->fresh()->balance, 2, '.', ''));
        $this->assertSame('95000.00', number_format((float) $seller->fresh()->balance, 2, '.', ''));

        $this->assertSame(
            '1.00000000',
            number_format((float) $buyer->assets()->where('symbol', 'BTC')->first()->amount, 8, '.', '')
        );

        $sellerAsset = $seller->assets()->where('symbol', 'BTC')->first();
        $this->assertSame('0.00000000', number_format((float) $sellerAsset->amount, 8, '.', ''));
        $this->assertSame('0.00000000', number_format((float) $sellerAsset->locked_amount, 8, '.', ''));
    }
}
