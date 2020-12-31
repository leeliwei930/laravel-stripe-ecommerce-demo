<?php

namespace  App\Adapters;

interface PaymentWebhookAdapter {


    public function setWebhookEvent($payload, $sigHeader, $challenge);

    public function getWebhookEvent();

}
