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
      
        Log::info("Starting import from $path");

        $this->info("Import queued.");
        return 0;
    }
}
