<?php

namespace App\Jobs;

use App\Enums\OrderStatusEnum;
use App\Models\Order;
use App\Services\FinalizeOrderService;
use App\Services\OrderFulfillmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SimulatePaymentJob implements ShouldQueue
{
    use Queueable;
    public string $orderCode;

    /**
     * Create a new job instance.
     */
    public function __construct(string $orderCode)
    {
        $this->orderCode = $orderCode;
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("Simulating payment for order code {$this->orderCode}");
        $success = rand(0, 100) > 50;
        $this->simulatePayment($this->orderCode, $success ? 'success' : 'failure');
    }

    private function simulatePayment($orderCode, $status): void
    {
        $order = Order::where('code',$orderCode)->first();
        if (!$order) {
            Log::warning("Order with code {$orderCode} not found during payment simulation.");
            return;
        }
        if ($order->status === OrderStatusEnum::COMPLETED || $order->status === OrderStatusEnum::FAILED) {
            Log::info("Order ID {$order->id} ({$order->code}) has already been finalized with status {$order->status}. Skipping payment simulation.");
            return;
        }

        if ($status === 'success') {
            Log::info("Payment succeeded for order ID {$order->id}");
            app(OrderFulfillmentService::class)->finalize($order);
        } else {
            Log::info("Payment failed for order ID {$order->id}");
            app(OrderFulfillmentService::class)->rollback($order);
        }
    }
}
