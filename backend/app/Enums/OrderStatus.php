<?php

namespace App\Enums;

enum OrderStatus: int
{
    case OPEN = 1;
    case FILLED = 2;
    case CANCELLED = 3;

    public function isFinal(): bool
    {
        return $this !== self::OPEN;
    }
}
