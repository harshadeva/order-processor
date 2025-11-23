<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class KPIService
{
    private static function dateKey($date)
    {
        return $date->format('Y-m-d');
    }

    public static function incrementRevenue(float $amount, $date)
    {
        $key = "kpi:revenue:" . self::dateKey($date);
        Redis::incrbyfloat($key, $amount);
    }

    public static function decrementRevenue(float $amount, $date)
    {
        $key = "kpi:revenue:" . self::dateKey($date);
        Redis::incrbyfloat($key, -$amount);
    }

    public static function incrementOrderCount($date)
    {
        $key = "kpi:order_count:" . self::dateKey($date);
        Redis::incr($key);
    }

    public static function incrementCustomerScore(int $customerId, float $amount)
    {
        Redis::zincrby("leaderboard:customers", $amount, $customerId);
    }

    public static function decreaseCustomerScore(int $customerId, float $amount)
    {
        Redis::zincrby("leaderboard:customers", -$amount, $customerId);
    }
}
