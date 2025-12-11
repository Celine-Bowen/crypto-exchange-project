<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Demo Trader',
            'email' => 'demo@example.com',
            'balance' => 250000,
        ]);

        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'amount' => 1.25,
            'locked_amount' => 0,
        ]);

        Asset::factory()->create([
            'user_id' => $user->id,
            'symbol' => 'ETH',
            'amount' => 10,
            'locked_amount' => 0,
        ]);
    }
}
