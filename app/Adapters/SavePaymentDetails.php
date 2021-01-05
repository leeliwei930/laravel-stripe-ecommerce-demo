<?php

namespace App\Adapters;

interface SavePaymentDetails {
    public function createSetupIntent($customerID);

    public function retrieveSetupIntent($setupIntentID);
}
