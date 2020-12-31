<?php

namespace App\Repositories;

use App\Adapters\Stripe\StripePaymentAdapter;
use App\Interfaces\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class OrderRepository implements OrderRepositoryInterface {

    protected $stripe;
    protected $user;
    public function __construct(StripePaymentAdapter $stripePaymentAdapter)
    {
        $this->stripe = $stripePaymentAdapter;
        $this->user = \Auth::user();
    }

    public function createPayment(PaymentMethod $paymentMethod, Order $order): Model
    {
        $amount = $order->calculateAmount();
        $paymentIntent = $this->stripe->createPaymentIntent([
            'customer' => $this->user->stripe_customer_id,
            'payment_method' => $paymentMethod->token,
            'amount' => $amount,
            'currency' => 'myr',
            'confirm' => true
        ]);
        return $order->payment()->create([
            'amount' => $amount,
            'status' => Payment::PENDING,
            'tx_no' => $paymentIntent->id,
        ]);
    }
}
