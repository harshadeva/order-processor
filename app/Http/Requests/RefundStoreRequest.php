<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;

class RefundStoreRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'         => 'required|exists:orders,id',
            'external_id'  => 'required|unique:refunds,idempotency_key',
            'amount'           => 'required|numeric|min:0.01',
            'reason'           => 'nullable|string|max:255',
        ];
    }

     public function after(): array
    {
        return [
            function ($validator) {
                $order = Order::find($this->order_id);

                if (! $order) {
                    return;
                }

                $refundableBalance = $order->total - $order->refunded_total;

                if ($this->amount > $refundableBalance) {
                    $validator->errors()->add(
                        'amount',
                        'Refund amount cannot exceed remaining refundable balance of ' . number_format($refundableBalance, 2)
                    );
                }
            }
        ];
    }
}
