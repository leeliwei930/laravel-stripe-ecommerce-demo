<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

\Illuminate\Support\Facades\Auth::loginUsingId(1);
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('webhooks')->group(function(){
    Route::post('stripe', \App\Http\Controllers\StripeWebhookController::class);
});
Route::post('checkout', [\App\Http\Controllers\CheckoutController::class, 'checkout']);
Route::get('products', [\App\Http\Controllers\ProductController::class, 'products']);

Route::prefix('cart')->group(function(){
    Route::get('/', [\App\Http\Controllers\CartController::class, 'all']);
    Route::put('add', [\App\Http\Controllers\CartController::class, 'add']);
    Route::put('remove', [\App\Http\Controllers\CartController::class, 'remove']);
});

Route::prefix('payment-methods')->group(function(){
    Route::get('/' , [\App\Http\Controllers\PaymentMethodController::class, 'all']);
    Route::get('/setup-intent', [\App\Http\Controllers\PaymentMethodController::class , 'createSetupIntent']);
    Route::post('/create' , [\App\Http\Controllers\PaymentMethodController::class, 'create']);
});

Route::prefix('orders')->group(function(){
    // can be use as callback url for others payment channel to update the order's payment status
    Route::get('/', [\App\Http\Controllers\OrderController::class, 'list']);
    Route::get('/{order}/payment/reconfirm', [\App\Http\Controllers\CheckoutController::class, 'reconfirmPayment']);
    // allow user can change the payment method for the order when the payment status is failed
    Route::put('/{order}/payment/update', [\App\Http\Controllers\OrderController::class, 'changePaymentMethod']);
    // cancel the order
     // -  order can be cancelled within 3 days after order paid
    // - the order payment status is in failed or requires action
    // if the payment is paid, which mean a refund need to be created.
    Route::post('/{order}/payment/cancel', [\App\Http\Controllers\OrderController::class, 'cancel']);
    Route::post('/{order}/payment/refund', [\App\Http\Controllers\OrderController::class, 'refund']);

});
