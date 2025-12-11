<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trade */
class TradeResource extends JsonResource
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
            'symbol' => $this->symbol,
            'price' => $this->price,
            'amount' => $this->amount,
            'total_value' => $this->total_value,
            'fee' => $this->fee,
            'executed_at' => $this->executed_at,
        ];
    }
}
