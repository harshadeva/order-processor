<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        DB::transaction(function () {

            Log::info('Processing order: ' ,[$this->data]);
            // $order = Order::firstOrCreate(
            //     ['code' => $this->data['order_code']],
            //     [
            //         'customer_id' => $this->data['customer_id'],
            //         'total' => $this->data['total']
            //     ]
            // );
        });

    }
}
