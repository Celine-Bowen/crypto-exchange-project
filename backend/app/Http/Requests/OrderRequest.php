<?php

namespace App\Http\Requests;

use App\Enums\OrderSide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'symbol' => ['required', 'string', 'max:10'],
            'side' => ['required', Rule::enum(OrderSide::class)],
            'price' => ['required', 'numeric', 'min:0.01'],
            'amount' => ['required', 'numeric', 'min:0.00000001'],
        ];
    }
}
