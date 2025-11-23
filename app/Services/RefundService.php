<?php

namespace App\Services;

use App\Jobs\RefundJob;
use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatusEnum;
use App\Models\StockReservation;
use App\Enums\StockReservationStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefundService
{
    public function refund(array $data): bool
    {
         RefundJob::dispatch($data);
    }
}
