<?php

namespace App\Enums;

enum OrderSide: string
{
    case BUY = 'buy';
    case SELL = 'sell';

    public function opposite(): self
    {
        return $this === self::BUY ? self::SELL : self::BUY;
    }
}
