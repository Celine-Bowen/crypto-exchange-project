<?php

namespace App\Events;

use App\Http\Resources\OrderResource;
use App\Http\Resources\TradeResource;
use App\Models\Trade;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $profiles
     */
    public function __construct(public Trade $trade, public array $profiles)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->trade->buyOrder->user_id),
            new PrivateChannel('user.'.$this->trade->sellOrder->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    public function broadcastWith(): array
    {
        return [
            'trade' => new TradeResource($this->trade),
            'buy_order' => new OrderResource($this->trade->buyOrder),
            'sell_order' => new OrderResource($this->trade->sellOrder),
            'profiles' => $this->profiles,
        ];
    }
}
