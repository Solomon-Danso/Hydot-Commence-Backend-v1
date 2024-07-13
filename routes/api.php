<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthenticator;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuditTrialController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAuthenticationController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('redirectToGateway', [PaymentController::class, 'redirectToGateway']);
Route::get('makePayment', [PaymentController::class, 'makePayment']);
Route::get('getPaymentData', [PaymentController::class, 'getPaymentData']);
Route::get('getAllCustomers', [PaymentController::class, 'getAllCustomers']);
Route::get('getAllTransactions', [PaymentController::class, 'getAllTransactions']);

// Route for setting up the admin, accessible without authentication
Route::post('SetUpCreateAdmin', [AdminUserController::class, 'SetUpCreateAdmin']);
Route::post('LogIn', [AuthenticationController::class, 'LogIn']);
Route::post('VerifyToken', [AuthenticationController::class, 'VerifyToken']);
Route::post('ForgetPasswordStep1', [AuthenticationController::class, 'ForgetPasswordStep1']);
Route::post('ForgetPasswordStep2', [AuthenticationController::class, 'ForgetPasswordStep2']);
Route::post('Visitors', [AuditTrialController::class, 'Visitors']);
Route::post('UnLocker', [AdminUserController::class, 'UnLocker']);
Route::post('Test', [AdminUserController::class, 'Test']);

Route::post('CustomerLogIn', [CustomerAuthenticationController::class, 'CustomerLogIn']);
Route::post('CustomerVerifyToken', [CustomerAuthenticationController::class, 'CustomerVerifyToken']);
Route::post('CustomerForgetPasswordStep1', [CustomerAuthenticationController::class, 'CustomerForgetPasswordStep1']);
Route::post('CustomerForgetPasswordStep2', [CustomerAuthenticationController::class, 'CustomerForgetPasswordStep2']);







// Routes that require authentication
Route::middleware([ApiAuthenticator::class])->group(function () {

    //Staff Members
    Route::post('SuspendAdmin', [AdminUserController::class, 'SuspendAdmin']);
    Route::post('UnSuspendAdmin', [AdminUserController::class, 'UnSuspendAdmin']);
    Route::post('BlockAdmin', [AdminUserController::class, 'BlockAdmin']);
    Route::post('UnBlockAdmin', [AdminUserController::class, 'UnBlockAdmin']);
    Route::post('CreateAdmin', [AdminUserController::class, 'CreateAdmin']);
    Route::post('UpdateAdmin', [AdminUserController::class, 'UpdateAdmin']);
    Route::post('ViewSingleAdmin', [AdminUserController::class, 'ViewSingleAdmin']);
    Route::post('DeleteAdmin', [AdminUserController::class, 'DeleteAdmin']);
    Route::post('ViewAllAdmin', [AdminUserController::class, ' ViewAllAdmin']);


    //Customers
    Route::post('CreateCustomer', [CustomerController::class, 'CreateCustomer']);
    Route::post('UpdateCustomer', [CustomerController::class, 'UpdateCustomer']);
    Route::post('ViewSingleCustomer', [CustomerController::class, 'ViewSingleCustomer']);
    Route::post('DeleteCustomer', [CustomerController::class, 'DeleteCustomer']);
    Route::post('ViewAllCustomer', [CustomerController::class, ' ViewAllCustomer']);









});






