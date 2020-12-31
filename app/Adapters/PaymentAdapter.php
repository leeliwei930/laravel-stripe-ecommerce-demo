<?php

namespace App\Adapters;

use Stripe\Customer;

interface PaymentAdapter
{

    public function createPaymentIntent($paymentIntentData);
    public function retrievePaymentIntent($paymentIntentID);
    public function cancelPaymentIntent($paymentIntentID);
    public function updatePaymentIntent($paymentIntentID, $paymentIntentData);

    public function confirmPaymentIntent($paymentIntentID, $stripePaymentMethodID);
}
