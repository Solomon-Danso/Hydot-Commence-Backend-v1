<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiAuthenticator;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuditTrialController;
use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerAuthenticationController;
use App\Http\Controllers\MenuCategoryProduct;
use App\Http\Middleware\CustomerAuthenticator;
use App\Http\Controllers\CartOrderPayment;
use App\Http\Controllers\BaggingCheckerDelivery;
use App\Http\Controllers\Master;


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

Route::get('payment/{UserId}/{OrderId}', [CartOrderPayment::class, 'Payment']);
Route::get('ConfirmPayment/{RefId}', [CartOrderPayment::class, 'ConfirmPayment']);







Route::middleware([CustomerAuthenticator::class])->group(function () {

/**********************************************
 *                                            *
 *   💳 PAYMENT ROUTES                        *
 *                                            *
 **********************************************/

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
Route::post('EditProductInDetailedOrder', [CartOrderPayment::class, 'EditProductInDetailedOrder']);
Route::post('DeleteProductInDetailedOrder', [CartOrderPayment::class, 'DeleteProductInDetailedOrder']);



});



// Routes that require authentication
Route::middleware([ApiAuthenticator::class])->group(function () {

/**********************************************
 *                                            *
 *   ⚙️ CONFIGURATIONS ROUTES                *
 *                                            *
 **********************************************/
    Route::post('RoleList', [AuditTrialController::class, 'RoleList']);
    Route::post('CreateUserRole', [AuditTrialController::class, 'CreateUserRole']);
Route::post('ViewUserFunctions', [AuditTrialController::class, 'ViewUserFunctions']);
Route::post('DeleteUserFunctions', [AuditTrialController::class, 'DeleteUserFunctions']);
Route::post('ViewAllPayment', [CartOrderPayment::class, 'ViewAllPayment']);
Route::post('ViewAuditTrail', [Master::class, 'ViewAuditTrail']);
Route::post('ViewCustomerTrail', [Master::class, 'ViewCustomerTrail']);
Route::post('ViewProductAssessment', [Master::class, 'ViewProductAssessment']);
Route::post('ViewRateLimitCatcher', [Master::class, 'ViewRateLimitCatcher']);
Route::post('ViewMasterRepo', [Master::class, 'ViewMasterRepo']);




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
    Route::post('BlockCustomer', [CustomerController::class, 'BlockCustomer']);
    Route::post('UnBlockCustomer', [CustomerController::class, 'UnBlockCustomer']);
    Route::post('SuspendCustomer', [CustomerController::class, 'SuspendCustomer']);
    Route::post('UnSuspendCustomer', [CustomerController::class, 'UnSuspendCustomer']);


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

/**********************************************
 *                                            *
 *   🛍️ BAGGING, ✅ CHECKER, 🚚 DELIVERY      *
 *                                            *
 **********************************************/

 Route::post('CheckBagging', [BaggingCheckerDelivery::class, 'CheckBagging']);
 Route::post('ViewBaggingList', [BaggingCheckerDelivery::class, 'ViewBaggingList']);
 Route::post('ViewConfirmedBaggingList', [BaggingCheckerDelivery::class, 'ViewConfirmedBaggingList']);
 Route::post('CheckChecker', [BaggingCheckerDelivery::class, 'CheckChecker']);
 Route::post('ViewCheckerList', [BaggingCheckerDelivery::class, 'ViewCheckerList']);
 Route::post('ViewConfirmedCheckerList', [BaggingCheckerDelivery::class, 'ViewConfirmedCheckerList']);
 Route::post('AssignForDelivery', [BaggingCheckerDelivery::class, 'AssignForDelivery']);
 Route::post('ViewUnAssignedDelivery', [BaggingCheckerDelivery::class, 'ViewUnAssignedDelivery']);
 Route::post('ViewAssignedDelivery', [BaggingCheckerDelivery::class, 'ViewAssignedDelivery']);
 Route::post('ViewSingleOrdersToDeliver', [BaggingCheckerDelivery::class, 'ViewSingleOrdersToDeliver']);
 Route::post('DeliverNow', [BaggingCheckerDelivery::class, 'DeliverNow']);
 Route::post('ViewSingleDeliveredOrders', [BaggingCheckerDelivery::class, 'ViewSingleDeliveredOrders']);
 Route::post('ViewDeliveredOrders', [BaggingCheckerDelivery::class, 'ViewDeliveredOrders']);

 Route::post('DetailedAllOrder', [CartOrderPayment::class, 'DetailedAllOrder']);
 Route::post('ViewGlobalDelivery', [BaggingCheckerDelivery::class, 'ViewGlobalDelivery']);











});






