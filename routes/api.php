<?php

use App\Http\Controllers\RefundController;
use Illuminate\Support\Facades\Route;

Route::get('up', function(){
    return 'Application is working';
});
Route::post('refunds', [RefundController::class, 'store']);
