<?php

namespace App\Interfaces;

use App\Models\Order;
use App\Models\PaymentMethod;


interface OrderRepositoryInterface {
    public function createPayment(PaymentMethod $paymentMethod, Order $order);
}
