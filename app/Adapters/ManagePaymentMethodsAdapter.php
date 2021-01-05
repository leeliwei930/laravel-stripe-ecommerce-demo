<?php

namespace App\Adapters;

use App\Models\User;
use Stripe\PaymentMethod;

interface ManagePaymentMethodsAdapter {

    public function createPaymentMethod($data);
    public function updatePaymentMethod();
    public function deletePaymentMethod();

    public function retrievePaymentMethod($stripePaymentMethodID);

}
