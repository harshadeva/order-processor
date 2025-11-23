<?php

namespace App\Enums;

enum RefundStatusEnum: int
{
    case PENDING = 1;
    case PROCESSED = 2;
    case FAILED = 3;
}
