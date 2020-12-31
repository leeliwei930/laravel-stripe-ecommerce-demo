<?php

namespace App\Http\Controllers;

use App\Adapters\Stripe\StripePaymentAdapter;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\CartRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckoutSession;
use function GuzzleHttp\Promise\all;

class CheckoutController extends Controller
{
    //
    protected $cartRepository;
    protected $orderRepository;
    protected $user;
    public function __construct(CartRepository $cartRepository, OrderRepository $orderRepository)
    {
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->user = Auth::user();
    }


    public function checkout(Request $request, StripePaymentAdapter $stripe)
    {
        $validator = Validator::make($request->toArray(),[
            'payment_method_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $paymentMethod = Auth::user()->paymentMethods()->find($value);
                    if(is_null($paymentMethod)){
                        $fail("Unable to use this payment method to perform a charge");
                    }
                },
            ],
            'cart_items' => [
                'required',
                'array',
                'max:30', // max 30 items to checkout,
                'min:1' // checkout at least 1 item
            ],
            'cart_items.*' => [
                'required',
                function ($attribute, $value, $fail) {
                    $cartItem = Auth::user()->retrieveCartItem($value);
                    $product = Product::find($cartItem->product_id);
                    if(is_null($cartItem)){
                        $fail("Unable to checkout the cart item id - $value");
                    }
                    if($product->trashed()){
                        $fail("Unable to checkout the product $product->name due to it has been removed by vendor.");
                    }

                    if(!$product->isAvailable()){
                        $fail("The product $product->name is out of stock.");
                    }
                },
            ]
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->toArray(), 422);
        }

        $order = $this->cartRepository->checkout($request->input('cart_items'));
        $paymentMethodID = $request->input('payment_method_id');

        $paymentMethod = $this->user->paymentMethods()->find($paymentMethodID);

        $payment = $this->orderRepository->createPayment($paymentMethod, $order);



        return response()->json(['payment'=> $payment->toArray()], 200);

    }


}
