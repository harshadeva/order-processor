<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Enums\OrderStatusEnum;
use App\Notifications\OrderSuccessNotification;
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


    /**
     * Create a new job instance.
     */
    public function __construct(protected array $data)
    {
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $reservationSuccess = false;
        DB::transaction(function () use (&$reservationSuccess): void {

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
                $statusName = OrderStatusEnum::from(OrderStatusEnum::PENDING->value)->name;
                Log::info("Order Code {$order->code} : has already been processed with status {$statusName}. Skipping to the next order.");
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
                        'total' => round(bcmul($item['unit_price'], $item['quantity'], 2),2)
                    ]
                );
            }

            $order->update([
                'total' => round(OrderItem::where('order_id', $order->id)->sum('total'),2),
            ]);

            $reserved = app(OrderFulfillmentService::class)->reserve($order);

            if (!$reserved) {
                Log::error("Order Code {$order->code} : processing failed due to insufficient stock.");
                return;
            }
            $reservationSuccess = true;
        },5);

        if (!$reservationSuccess) {
            Log::info("Order Code {$this->data['order_code']} : Skipping payment simulation.reservation not success.");
            return;
        }
        
        Log::info("Order Code {$this->data['order_code']} : Dispatching payment simulation");
        SimulatePaymentJob::dispatch($this->data['order_code']);
    }
}
