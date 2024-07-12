<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('redirectToGateway', [PaymentController::class, 'redirectToGateway']);
Route::get('makePayment', [PaymentController::class, 'makePayment']);
Route::get('getPaymentData', [PaymentController::class, 'getPaymentData']);
Route::get('getAllCustomers', [PaymentController::class, 'getAllCustomers']);
Route::get('getAllTransactions', [PaymentController::class, 'getAllTransactions']);






