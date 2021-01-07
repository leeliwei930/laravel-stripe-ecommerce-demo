<?php

namespace App\Interfaces;

use App\Models\Order;
use App\Models\PaymentMethod;
use Stripe\PaymentIntent;


interface OrderRepositoryInterface {
    public function createPayment(PaymentIntent $paymentIntent, PaymentMethod $paymentMethod, Order $order);

    public function list();

    public function retrieveOrder($order, $relations);

}
