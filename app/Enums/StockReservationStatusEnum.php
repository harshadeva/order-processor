<?php

namespace App\Enums;

enum StockReservationStatusEnum: int
{
    case RESERVED = 1;
    case RELEASED = 2;
    case CONSUMED = 3;
}
