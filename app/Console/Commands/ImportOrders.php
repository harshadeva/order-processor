<?php

namespace App\Console\Commands;

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
        if (!file_exists($path)) {
            $this->error("File not found: $path");
            return 1;
        }

        Log::info("Starting import from $path");

        LazyCollection::make(function () use ($path) {
            $handle = fopen($path, 'r');
            while (($line = fgetcsv($handle)) !== false) {
                yield $line;
            }
            fclose($handle);
        })->skip(1) // if header
            ->chunk(200)
            ->each(function ($chunk) {
                foreach ($chunk as $row) {
                    // map CSV row to data array
                    $data = $this->mapRow($row);
                    Log::info('Queuing order', $data);
                    // dispatch job per order
                    // ProcessOrderJob::dispatch($data)->onQueue('orders');
                }
            });

        $this->info("Import queued.");

        $this->info("Import queued.");
        return 0;
    }
    private function mapRow(array $row)
    {
        return [
            'order_id'     => $row[0],
            'customer_id'  => $row[1],
            'product_id'   => $row[2],
            'quantity'     => (int) $row[3],
            'unit_price'   => (float) $row[4],
        ];
    }
}
