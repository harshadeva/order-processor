<?php

namespace App\Enums;

enum OrderStatusEnum: int
{
    case PENDING = 1;
    case RESERVED = 2;
    case PROCESSING = 3;
    case COMPLETED = 4;
    case FAILED = 5;
}