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
    protected $stripe;
    public function __construct(CartRepository $cartRepository, OrderRepository $orderRepository, StripePaymentAdapter $stripePaymentAdapter)
    {
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->user = Auth::user();
        $this->stripe = $stripePaymentAdapter;
    }


    public function checkout(Request $request, StripePaymentAdapter $stripe)
    {
        // validate all the payment method, selected cart items for checkout
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

        // Checkout an order from selected cart items
        $order = $this->cartRepository->checkout($request->input('cart_items'));


        $paymentMethodID = $request->input('payment_method_id');

        // retrieve laravel payment method id
        $paymentMethod = $this->user->paymentMethods()->find($paymentMethodID);

        // create payment intent
        $paymentIntent = $this->stripe->createPaymentIntent([
            'description' => "Payment for order $order->id",
            'customer' => $this->user->stripe_customer_id, // use the current logged in user's stripe_customer_id
            'payment_method' => $paymentMethod->token, // use the stripe_payment_method_id that we stored before with the payment method
            'amount' => $order->calculateAmount(), // total amount, Be careful this is calculated in cents
            'currency' => 'myr', // Malaysia Ringgit currency
            'confirmation_method' => 'manual', // the confirmation will be done on frontend as manual, such as 3D secure card authorization flow
            'confirm' => true, // try to confirm this payment
        ]);

        // if stripe can't create the payment intent return error to frontend
        if($this->stripe->anyError()){
            return response()->json([
                'error' => 'stripe',
                'message' => $this->stripe->getError()->getMessage()
            ], 422);
        }

        // create a payment and attach to the order
        $payment = $this->orderRepository->createPayment($paymentIntent, $paymentMethod, $order);

        // response the stripe payment_intent data for this order
        return response()->json([
            'order' => $order->toArray(),
            'payment_intent'=> $paymentIntent->toArray()
        ], 200);

    }

    // use to probe with third party payment gateway payment status
    public function reconfirmPayment($order, Request $request)
    {
        $order = $this->user->orders()->find($order);

        $order->load('payment.paymentMethod.paymentGateway');

        $payment = $order['payment'];

        $paymentMethod = $payment['paymentMethod'];
        $paymentGateway = $paymentMethod['paymentGateway'];

        return $this->handleReconfirmPayment($paymentGateway->name, $payment->tx_no);

    }

    private function handleReconfirmPayment($paymentGatewayName, $transactionRefNumber){
        switch($paymentGatewayName){
            case "stripe":
                return $this->reconfirmStripePaymentIntent($transactionRefNumber);
        }
    }

    private function reconfirmStripePaymentIntent($transactionRefNumber){
        $paymentIntent = $this->stripe->retrievePaymentIntent($transactionRefNumber);

        // reconfirm the payment intent
        $paymentIntent->confirm();

        if($this->stripe->anyError()){
            return response()->json([
                'message' => $this->stripe->getError()->getMessage()
            ], 422);
        }

        return response()->json(['payment_intent' => $paymentIntent->toArray()]);
    }
}
