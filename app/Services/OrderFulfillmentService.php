<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatusEnum;
use App\Models\StockReservation;
use App\Enums\StockReservationStatusEnum;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderFulfillmentService
{
    public function reserve(Order $order): bool
    {
        return DB::transaction(function () use($order) {
            foreach ($order->orderItems as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                $available = $product->stock - $product->reserved;
                if ($available < $item->qty) {
                    Log::info("-- Order Code {$order->code} : Insufficient stock for product ID {$product->id}. Available: {$available}, Required: {$item->qty}");
                    $this->rollback($order);
                    return false;
                }

                $product->reserved += $item->qty;
                $product->save();

                StockReservation::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'qty' => $item->qty,
                    'status' => StockReservationStatusEnum::RESERVED,
                ]);
                Log::info("-- Order Code {$order->code} : Reserved {$item->qty} units of product ID {$product->id}");
            }
            $order->update(['status' => OrderStatusEnum::RESERVED]);
            return true;
        });
    }

    public function rollback(Order $order): void
    {
        DB::transaction(function () use($order): void {
            Log::info("-- Order Code {$order->code} : Stock releasing");

            $reservations = StockReservation::where('order_id', $order->id)
                ->where('status', StockReservationStatusEnum::RESERVED)
                ->get();
            foreach ($reservations as $reservation) {
                $product = Product::where('id', $reservation->product_id)->lockForUpdate()->first();
                $product->reserved -= $reservation->qty;
                $product->save();

                $reservation->update(['status' => StockReservationStatusEnum::RELEASED]);
                Log::info("-- Order Code {$order->code} : Released reservation of {$reservation->qty} units of product ID {$product->id}");
            }

            $order->update(['status' => OrderStatusEnum::FAILED]);
            Log::info("-- Order Code {$order->code} : marked as failed");
        });
    }


    public function finalize(Order $order): void
    {
        DB::transaction(function () use($order): void {
            Log::info("-- Order Code {$order->code} : Finalizing order");

            $reservations = StockReservation::where('order_id', $order->id)
                ->where('status', StockReservationStatusEnum::RESERVED)
                ->lockForUpdate()
                ->get();

            foreach ($reservations as $reservation) {

                $product = Product::where('id', $reservation->product_id)
                    ->lockForUpdate()
                    ->first();

                Log::debug('reserved qty', [$reservation->qty]);
                $product->stock -= $reservation->qty;
                $product->reserved -= $reservation->qty;
                $product->save();

                $reservation->update(['status' => StockReservationStatusEnum::CONSUMED]);
            }

            $order->update(['status' => OrderStatusEnum::COMPLETED]);

            Log::info("-- Order Code {$order->code} : completed successfully");
        });
    }
}
