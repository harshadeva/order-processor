<?php

namespace App\Services;

use App\Jobs\RefundJob;
use App\Enums\RefundStatusEnum;
use App\Models\Refund;

class RefundService
{
    public function refund(array $data)
    {
        $record = Refund::updateOrCreate(
            [
                'idempotency_key' => $data['external_id'],
                'order_id' => $data['order_id'],
            ],
            [
                'amount'   => $data['amount'],
                'reason' => $data['reason'] ?? null,
                'status' => RefundStatusEnum::PENDING
            ]
        );
        RefundJob::dispatch($record->id);
    }
}
