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
use App\Http\Controllers\MenuCategoryProduct;
use App\Http\Middleware\CustomerAuthenticator;
use App\Http\Controllers\CartOrderPayment;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


/**********************************************
 *                                            *
 *   🌐 GLOBAL ROUTES                        *
 *                                            *
 **********************************************/

Route::post('SetUpCreateAdmin', [AdminUserController::class, 'SetUpCreateAdmin']);
Route::post('LogIn', [AuthenticationController::class, 'LogIn']);
Route::post('VerifyToken', [AuthenticationController::class, 'VerifyToken']);
Route::post('ForgetPasswordStep1', [AuthenticationController::class, 'ForgetPasswordStep1']);
Route::post('ForgetPasswordStep2', [AuthenticationController::class, 'ForgetPasswordStep2']);
Route::post('Visitors', [AuditTrialController::class, 'Visitors']);
Route::post('UnLocker', [AdminUserController::class, 'UnLocker']);
Route::post('CustomerLogIn', [CustomerAuthenticationController::class, 'CustomerLogIn']);
Route::post('CustomerVerifyToken', [CustomerAuthenticationController::class, 'CustomerVerifyToken']);
Route::post('CustomerForgetPasswordStep1', [CustomerAuthenticationController::class, 'CustomerForgetPasswordStep1']);
Route::post('CustomerForgetPasswordStep2', [CustomerAuthenticationController::class, 'CustomerForgetPasswordStep2']);
Route::post('RoleList', [AuditTrialController::class, 'RoleList']);
Route::post('ViewMenu', [MenuCategoryProduct::class, 'ViewMenu']);
Route::post('ViewCategory', [MenuCategoryProduct::class, 'ViewCategory']);
Route::post('ViewProduct', [MenuCategoryProduct::class, 'ViewProduct']);
Route::post('ViewSingleProduct', [MenuCategoryProduct::class, 'ViewSingleProduct']);
Route::post('TestRateLimit', [MenuCategoryProduct::class, 'TestRateLimit']);
Route::post('CreateCustomer', [CustomerController::class, 'CreateCustomer']);
Route::post('ViewCategory', [MenuCategoryProduct::class, 'ViewCategory']);
Route::post('ViewMenu', [MenuCategoryProduct::class, 'ViewMenu']);
Route::get('makePayment', [PaymentController::class, 'makePayment']);

Route::get('payment/{UserId}/{OrderId}', [CartOrderPayment::class, 'Payment']);



Route::middleware([CustomerAuthenticator::class])->group(function () {

/**********************************************
 *                                            *
 *   💳 PAYMENT ROUTES                        *
 *                                            *
 **********************************************/
Route::get('redirectToGateway', [PaymentController::class, 'redirectToGateway']);
Route::get('getPaymentData', [PaymentController::class, 'getPaymentData']);
Route::get('getAllCustomers', [PaymentController::class, 'getAllCustomers']);
Route::get('getAllTransactions', [PaymentController::class, 'getAllTransactions']);

/**********************************************
 *                                            *
 *   🧍 CUSTOMERS ROUTES                      *
 *                                            *
 **********************************************/
Route::post('UpdateCustomer', [CustomerController::class, 'UpdateCustomer']);
Route::post('ViewSingleCustomer', [CustomerController::class, 'ViewSingleCustomer']);

Route::post('AddToCart', [CartOrderPayment::class, 'AddToCart']);
Route::post('UpdateCart', [CartOrderPayment::class, 'UpdateCart']);
Route::post('ViewAllCart', [CartOrderPayment::class, 'ViewAllCart']);
Route::post('DeleteCart', [CartOrderPayment::class, 'DeleteCart']);

Route::post('AddToOrder', [CartOrderPayment::class, 'AddToOrder']);
Route::post('ViewAllOrder', [CartOrderPayment::class, 'ViewAllOrder']);
Route::post('DetailedOrder', [CartOrderPayment::class, 'DetailedOrder']);




});



// Routes that require authentication
Route::middleware([ApiAuthenticator::class])->group(function () {

/**********************************************
 *                                            *
 *   ⚙️ CONFIGURATIONS ROUTES                *
 *                                            *
 **********************************************/
    Route::post('RoleList', [AuditTrialController::class, 'RoleList']);



/**********************************************
 *                                            *
 *   🧑‍💼 STAFF MEMBERS ROUTES               *
 *                                            *
 **********************************************/
    Route::post('SuspendAdmin', [AdminUserController::class, 'SuspendAdmin']);
    Route::post('UnSuspendAdmin', [AdminUserController::class, 'UnSuspendAdmin']);
    Route::post('BlockAdmin', [AdminUserController::class, 'BlockAdmin']);
    Route::post('UnBlockAdmin', [AdminUserController::class, 'UnBlockAdmin']);
    Route::post('CreateAdmin', [AdminUserController::class, 'CreateAdmin']);
    Route::post('UpdateAdmin', [AdminUserController::class, 'UpdateAdmin']);
    Route::post('ViewSingleAdmin', [AdminUserController::class, 'ViewSingleAdmin']);
    Route::post('DeleteAdmin', [AdminUserController::class, 'DeleteAdmin']);
    Route::post('ViewAllAdmin', [AdminUserController::class, 'ViewAllAdmin']);


/**********************************************
 *                                            *
 *   🧍 CUSTOMERS ROUTES                      *
 *                                            *
 **********************************************/
    Route::post('DeleteCustomer', [CustomerController::class, 'DeleteCustomer']);
    Route::post('ViewAllCustomer', [CustomerController::class, 'ViewAllCustomer']);

/**********************************************
 *                                            *
 *   📂 MENU CATEGORY PRODUCT ROUTES          *
 *                                            *
 **********************************************/

    Route::post('CreateMenu', [MenuCategoryProduct::class, 'CreateMenu']);
       Route::post('DeleteMenu', [MenuCategoryProduct::class, 'DeleteMenu']);

    Route::post('CreateCategory', [MenuCategoryProduct::class, 'CreateCategory']);
    Route::post('UpdateCategory', [MenuCategoryProduct::class, 'UpdateCategory']);
    Route::post('ViewSingleCategory', [MenuCategoryProduct::class, 'ViewSingleCategory']);
    Route::post('DeleteCategory', [MenuCategoryProduct::class, 'DeleteCategory']);

    Route::post('CreateProduct', [MenuCategoryProduct::class, 'CreateProduct']);
    Route::post('UpdateProduct', [MenuCategoryProduct::class, 'UpdateProduct']);
       Route::post('DeleteProduct', [MenuCategoryProduct::class, 'DeleteProduct']);














});






