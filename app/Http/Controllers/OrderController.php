<?php

namespace App\Http\Controllers;

use App\Adapters\ManageRefund;
use App\Adapters\Stripe\StripePaymentAdapter;
use App\Models\Order;
use App\Models\Payment;
use App\Repositories\OrderRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $orderRepository;
    protected $stripe;
    public function __construct(OrderRepository $orderRepository, StripePaymentAdapter $stripe)
    {
        $this->orderRepository = $orderRepository;
        $this->stripe = $stripe;
    }

    public function list()
    {
        $orders = $this->orderRepository->list();
        return response()->json(['orders' => $orders]);
    }

    public function changePaymentMethod($order, Request $request)
    {
        $validator = \Validator::make($request->all() , [
            'payment_method_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    $paymentMethod = Auth::user()->payment_methods()->find($value);
                    if(is_null($paymentMethod)){
                        $fail("Unable to use this payment method to perform a charge");
                    }
                },
            ],
        ]);
        $order = $this->orderRepository->retrieveOrder($order);
        if(is_null($order)){
            return response()->json([
                'status' => 'Unable to locate this order'
            ],404);
        }

        if($validator->fails()){
            return response()->json([
                'errors' => $validator->errors()->toArray()
            ],422);
        }
        $newPaymentMethod = Auth::user()->payment_methods()->find($request->input('payment_method_id'));

        // update the payment payment method id
        $order->load('payment.payment_method.payment_gateway');
        $payment = $order['payment'];
        $paymentMethod = $payment['payment_method'];
        // update the payment intent stripe payment method id
        if($paymentMethod['payment_gateway']->name === 'stripe'){
            $this->stripe->updatePaymentIntent($payment->tx_no, [
                'payment_method' => $paymentMethod->token
            ]);
            if($this->stripe->anyError()){
                return response()->json([
                    'error' => $this->stripe->getError()->getMessage()
                ],422);
            }
        }
        $order = $this->orderRepository->updatePaymentMethod($order, $newPaymentMethod);
        return response()->json([
            'order' => $order->toArray()
        ], 201);

    }

    public function cancel($order)
    {
        $order = $this->orderRepository->retrieveOrder($order, ['payment.payment_method.payment_gateway']);

        $payment = $order['payment'];
        $paymentMethod = $payment['payment_method'];
        $paymentGateway = $paymentMethod['payment_gateway'];
        if($paymentGateway->name === 'stripe'){
            return $this->handleStripePaymentCancellation($order);
        }
    }

    public function refund($order)
    {
        $order = $this->orderRepository->retrieveOrder($order, ['payment.payment_method.payment_gateway']);
        $payment = $order['payment'];
        $paymentMethod = $payment['payment_method'];
        $paymentGateway = $paymentMethod['payment_gateway'];
        if($paymentGateway->name === 'stripe'){
            return $this->handleStripePaymentRefund($order);
        }

    }
    private function handleStripePaymentRefund(Order $order){
        $payment = $order['payment'];
        if($order->refundable()) {
            // create refund
            $refund = $this->stripe->createRefund($payment->tx_no, [
                'reason' =>  'requested_by_customer'
            ]);
            if($this->stripe->anyError()){
                return response()->json([
                    'error' => $this->stripe->getError()->getMessage()
                ],422);
            }
            $order = $order->createRefund($refund->id);
        } else {
            return response()->json([
                'error' => 'Unable to make a refund for this order'
            ],422);
        }

        return response()->json(['order' => $order->toArray()]);

    }
    private function handleStripePaymentCancellation(Order $order){
        $payment = $order['payment'];
        if($order->cancellable()){
            $this->stripe->cancelPaymentIntent($payment->tx_no, [
                'cancellation_reason' => 'requested_by_customer'
            ]);
            if($this->stripe->anyError()){
                return response()->json([
                    'error' => $this->stripe->getError()->getMessage()
                ],422);
            }
            $order  = $order->cancel();
        } else {
            return response()->json([
                'error' => "Unable to cancel the payment"
            ],422);
        }

        return response()->json([
            'order' => $order->toArray()
        ]);
    }
}
