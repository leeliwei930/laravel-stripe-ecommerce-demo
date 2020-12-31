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
