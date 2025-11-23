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
}
