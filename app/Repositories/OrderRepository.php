<?php

namespace App\Repositories;

use App\Adapters\Stripe\StripePaymentAdapter;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Stripe\PaymentIntent;

class OrderRepository implements OrderRepositoryInterface {

    protected $stripe;
    protected $user;
    public function __construct(StripePaymentAdapter $stripePaymentAdapter)
    {
        $this->stripe = $stripePaymentAdapter;
        $this->user = \Auth::user();
    }

    public function createPayment(PaymentIntent $paymentIntent, PaymentMethod $paymentMethod,  Order $order): Model
    {

        return $order->payment()->create([
            'amount' =>  $order->calculateAmount(),
            'status' => Payment::PENDING,
            'tx_no' => $paymentIntent->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
    }

    public function list() : Collection
    {
        return $this->user->orders()->with([
            'payment.payment_method.payment_gateway',
            'items'
        ])->get();
    }

    public function retrieveOrder($order, $relations = []) : ?Order
    {
        return $this->user->orders()->with($relations)->find($order);
    }

    public function updatePaymentMethod(Order $order, PaymentMethod $paymentMethod):? Order
    {
        $order->load('payment.payment_method.payment_gateway');
        $payment = $order['payment'];

        $payment->update([
            'payment_method_id' => $paymentMethod->id
        ]);


        return $order;
    }

}
