<?php

namespace App\Jobs;

use App\Enums\RefundStatusEnum;
use App\Models\Order;
use App\Models\Refund;
use App\Services\KPIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RefundJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $refundId) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing refund: ', [$this->refundId]);

        $refund = Refund::lockForUpdate()->findOrFail($this->refundId);
        $order  = Order::lockForUpdate()->findOrFail($refund->order_id);

        if ($refund->status === RefundStatusEnum::PROCESSED) {
            return;
        }

        $remaining = $order->total - $order->refund_total;

        if ($refund->amount > $remaining) {
            $refund->update(['status' => RefundStatusEnum::FAILED]);
            return;
        }

        $order->refund_total += $refund->amount;
        $order->save();

        $refund->update([
            'status'       => RefundStatusEnum::PROCESSED,
            'processed_at' => now(),
        ]);
        Log::info('Refund processed: ', [$refund]);

         // Update KPIs
        KPIService::decrementRevenue($refund->amount, $order->created_at);
        KPIService::decreaseCustomerScore($order->customer_id, $refund->amount);
        Log::info('Leaderboard updated: ' . $refund->id);
    }
}
