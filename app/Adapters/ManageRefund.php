<?php

namespace App\Adapters;

interface ManageRefund {
    public function createRefund($paymentIntentID, $refundPayload);
}
