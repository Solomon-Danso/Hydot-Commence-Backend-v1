<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('redirectToGateway', [PaymentController::class, 'redirectToGateway']);
Route::get('handleGatewayCallback', [PaymentController::class, 'handleGatewayCallback']);

//Route::post('/pay', 'PaymentController@redirectToGateway')->name('pay');
