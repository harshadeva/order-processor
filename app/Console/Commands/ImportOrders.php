<?php

namespace App\Console\Commands;

use App\Jobs\ProcessOrderJob;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class ImportOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:import {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import orders from CSV and queue them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->argument('file');
        $sortedPath   = storage_path('app/public/sorted_orders.csv');
        $documentSortError = $this->sortDocument($path,$sortedPath);
        if($documentSortError != null){
            return $documentSortError;
        }

        $handle = fopen($sortedPath, 'r');

        $currentOrderCode = null;
        $currentOrderData = [
            'order_code' => null,
            'customer_id' => null,
            'items' => [],
            'total' => 0
        ];

        $headerSkipped = false;

        while (($row = fgetcsv($handle)) !== false) {

            // skip header
            if (!$headerSkipped) {
                $headerSkipped = true;
                continue;
            }
            // Log::info('row', [$row]);
            $mapped = $this->mapRow($row);

            $orderCode = $mapped['order_code'];

            if ($currentOrderCode === null) {
                $currentOrderCode = $orderCode;
            }

            // create new order and dispatch previous order
            if ($orderCode !== $currentOrderCode) {

                $this->dispatchOrder($currentOrderData);

                $currentOrderCode = $orderCode;
                $currentOrderData = [
                    'order_code'  => $orderCode,
                    'customer_id' => $mapped['customer_id'],
                    'items'       => [],
                    'total'       => 0,
                ];
            }

            // Add item to current order buffer
            $currentOrderData['order_code'] = $orderCode;
            $currentOrderData['customer_id'] = $mapped['customer_id'];
            $currentOrderData['items'][] = [
                'product_id' => $mapped['product_id'],
                'quantity'   => $mapped['quantity'],
                'unit_price' => $mapped['unit_price']
            ];
            $currentOrderData['total'] +=  $mapped['total'];
        }

        // flush last order
        $this->dispatchOrder($currentOrderData);

        fclose($handle);

        $this->info("Orders import queued.");
    }

    private function sortDocument($path,$sortedPath): ?string
    {
        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return 'File not found';
        }
        Log::info("Importing orders from file: $path");

        $bodyPath = storage_path('app/public/orders_body.csv');

        $handle = fopen($path, 'r');
        $header = fgets($handle);
        $bodyHandle = fopen($bodyPath, 'w');

        while (($line = fgets($handle)) !== false) {
            fwrite($bodyHandle, $line);
        }
        fclose($handle);
        fclose($bodyHandle);

        $cmd = sprintf(
            'sort -t"," -k2,2 %s > %s',
            escapeshellarg($bodyPath),
            escapeshellarg($sortedPath)
        );

        exec($cmd, $output, $status);

        if ($status !== 0) {
            $this->error("Sorting failed with status code: $status");
            return 'Sorting failed';
        }

        file_put_contents($sortedPath, $header . file_get_contents($sortedPath));

        $this->info("Sorting complete. File saved to: $sortedPath");
        return null;
    }

    private function mapRow(array $row)
    {
        return [
            'order_code'     => $row[1],
            'customer_id'  => $row[2],
            'product_id'   => $row[3],
            'quantity'     => (int) $row[4],
            'unit_price'   => (float) $row[5],
            'total'   => (float) $row[6],
        ];
    }

    private function dispatchOrder($orderData)
    {
        ProcessOrderJob::dispatch($orderData);
    }
}
