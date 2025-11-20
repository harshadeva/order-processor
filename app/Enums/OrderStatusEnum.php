<?php

namespace App\Enums\Enums;

enum OrderStatusEnum
{
    public const PENDING = 1;
    public const RESERVED = 2;
    public const PROCESSING = 3;
    public const COMPLETED = 4;
    public const FAILED = 5;
}
