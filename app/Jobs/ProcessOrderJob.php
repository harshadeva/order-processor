<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Enums\OrderStatusEnum;
use App\Services\OrderFulfillmentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    public $data;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function (): void {

            Log::info('Processing order: ', [$this->data]);
            $order = Order::firstOrCreate(
                ['code' => $this->data['order_code']],
                [
                    'customer_id' => $this->data['customer_id'],
                    'total' => 0,
                    'status' => OrderStatusEnum::PENDING,
                ]
            );

            // Prevent duplicate processing
            if ($order->status !== OrderStatusEnum::PENDING) {
                Log::info("Order ID {$order->id} ({$order->code}) has already been processed with status {$order->status}. Skipping.");
                return;
            }

            foreach ($this->data['items'] as $item) {
                OrderItem::firstOrCreate(
                    [
                        'order_id'   => $order->id,
                        'product_id' => $item['product_id'],
                    ],
                    [
                        'qty'   => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total' => bcmul($item['unit_price'], $item['quantity'], 2)
                    ]
                );
            }

            $order->update([
                'total' => OrderItem::where('order_id', $order->id)->sum('total'),
            ]);

            $reserved = app(OrderFulfillmentService::class)->reserve($order);

            if (! $reserved) {
                Log::error("Order ID {$order->id} processing failed due to insufficient stock.");
                return;
            }
        });
        Log::info("Dispatching payment simulation for order code {$this->data['order_code']}");
        SimulatePaymentJob::dispatch($this->data['order_code']);
    }
}
