<?php

namespace App\Helpers;

class TradePriceResolver
{
    public static function unit($tradePrice): float
    {
        $v = $tradePrice !== null ? (float) $tradePrice : 0.0;
        if (!is_finite($v) || $v < 0) {
            return 0.0;
        }
        return $v;
    }
}

