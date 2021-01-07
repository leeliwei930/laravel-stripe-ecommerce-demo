<?php

namespace App\Adapters;

use App\Models\Payment;
use Stripe\Customer;

interface PaymentAdapter
{

    public function createPaymentIntent($paymentIntentData);
    public function retrievePaymentIntent($paymentIntentID);
    public function cancelPaymentIntent($paymentIntentID, $cancelPaymentIntentPayload);
    public function updatePaymentIntent($paymentIntentID, $paymentIntentData);
    public function confirmPaymentIntent(Payment $payment);


}
