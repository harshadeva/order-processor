<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RefundStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'amount'   => 'required|numeric|min:0.01',
            'reason'   => 'nullable|string|max:255',
        ];
    }

    /**
     * Add custom validation after default rules.
     */
    public function after(): array
    {
        return [
            function () {
                $order = Order::find($this->order_id);

                if (! $order) {
                    return;
                }

                $refundableBalance = $order->total - $order->refunded_total;
                if ($this->amount > $refundableBalance) {
                    $this->errors()->add(
                        'amount',
                        'Refund amount cannot exceed the remaining refundable balance of ' . number_format($refundableBalance, 2)
                    );
                }
            }
        ];
    }
}
