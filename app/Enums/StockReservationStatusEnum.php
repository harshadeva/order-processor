<?php

namespace App\Enums;

enum StockReservationStatusEnum
{
    public const RESERVED = 1;
    public const RELEASED = 2;
    public const CONSUMED = 3;
}
