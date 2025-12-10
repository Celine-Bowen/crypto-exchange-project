<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'symbol' => $this->symbol,
            'side' => $this->side?->value,
            'price' => $this->price,
            'amount' => $this->amount,
            'status' => $this->status?->name,
            'status_code' => $this->status?->value,
            'reserved_value' => $this->reserved_value,
            'filled_at' => $this->filled_at,
            'created_at' => $this->created_at,
        ];
    }
}
