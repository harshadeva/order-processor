<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Enums\OrderStatusEnum;
use App\Models\StockReservation;
use App\Enums\StockReservationStatusEnum;
use Illuminate\Support\Facades\Log;

class ReserveStockService
{
    public function reserve(Order $order): bool
    {
        foreach ($order->orderItems as $item) {
            $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
            $available = $product->stock - $product->reserved;
            if ($available < $item->qty) {
                Log::info("Insufficient stock for product ID {$product->id}. Available: {$available}, Required: {$item->qty}");
                $this->releaseReservations($order);
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
            Log::info("Reserved {$item->qty} units of product ID {$product->id} for order ID {$order->id}");
        }
        $order->update(['status' => OrderStatusEnum::RESERVED]);
        return true;
    }

    private function releaseReservations(Order $order)
    {
        /* mark reservations as released & decrement reserved qty from product */
        $reservations = StockReservation::where('order_id', $order->id)
            ->where('status', StockReservationStatusEnum::RESERVED)
            ->get();
        foreach ($reservations as $reservation) {
            $product = Product::where('id', $reservation->product_id)->lockForUpdate()->first();
            $product->reserved -= $reservation->qty;
            $product->save();

            $reservation->update(['status' => StockReservationStatusEnum::RELEASED]);
            Log::info("Released reservation of {$reservation->qty} units of product ID {$product->id} for order ID {$order->id}");
        }
    }

    private function consumeReservations(Order $order)
    {
        /* mark reservations as consumed and decrement stock & reserved qty from product */
        $reservations = StockReservation::where('order_id', $order->id)
            ->where('status', StockReservationStatusEnum::RESERVED)
            ->get();
        foreach ($reservations as $reservation) {
            $product = Product::where('id', $reservation->product_id)->lockForUpdate()->first();
            $product->stock -= $reservation->qty;
            $product->reserved -= $reservation->qty;
            $product->save();

            $reservation->update(['status' => StockReservationStatusEnum::CONSUMED]);
            Log::info("Consumed reservation of {$reservation->qty} units of product ID {$product->id} for order ID {$order->id}");
        }
    }
}
