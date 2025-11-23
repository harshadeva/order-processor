<?php

namespace App\Http\Controllers;

use App\Http\Requests\RefundStoreRequest;
use App\Services\RefundService;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(private RefundService $refundService) {}
    public function store(RefundStoreRequest $request)
    {
        $this->refundService->refund($request->validated());
        return response()->json([
            'message' => 'Refund request created successfully.'
        ], 201);
    }
}
