<?php

namespace App\Adapters\Stripe;

use App\Adapters\ManagePaymentMethodsAdapter;
use App\Adapters\PaymentAdapter;
use App\Adapters\ManageCustomer;
use App\Adapters\PaymentWebhookAdapter;

use Stripe\Customer;
use Stripe\Event;

use App\Models\User;
use Stripe\Exception\SignatureVerificationException;

use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

class StripePaymentAdapter implements PaymentAdapter, ManageCustomer, PaymentWebhookAdapter, ManagePaymentMethodsAdapter {

    protected $webhookEvent;
    protected $webhookError = null;
    protected $stripe;
    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.key'));
    }

    public function createPaymentIntent($paymentIntentData): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->create([
            'customer' => $paymentIntentData['customer'],
            'payment_method' => $paymentIntentData['payment_method'],
            'amount' => $paymentIntentData['amount'],
            'currency' => $paymentIntentData['currency'],
            'confirm' => $paymentIntentData['confirm']
        ]);
    }

    public function retrievePaymentIntent($paymentIntentID): \Stripe\PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve($paymentIntentID);
    }

    public function confirmPaymentIntent($paymentIntentID, $stripePaymentMethodID)
    {
        // TODO: Implement confirmPaymentIntent() method.
    }

    public function cancelPaymentIntent($paymentIntentID)
    {
        return $this->stripe->paymentIntents->cancel($paymentIntentID);
    }

    public function updatePaymentIntent($paymentIntentID, $paymentIntentData): PaymentIntent
    {
        return $this->stripe->paymentIntents->update($paymentIntentID, [
            'amount' => $paymentIntentData['amount'],
            'currency' => $paymentIntentData['currency']
        ]);
    }

    public function createCustomer(\App\Models\User $user)
    {
        return $this->stripe->customers->create([
            'name' => $user->name,
            'email' => $user->email
        ]);
    }

    public function updateCustomer(\App\Models\User $user)
    {
        $primaryPaymentMethod = $user->getPrimaryPaymentMethod();

        return $this->stripe->customers->update($user->stripe_customer_id, [
            'source' => $primaryPaymentMethod->token,
            'name' => $user->name,
            'email' => $user->email
        ]);
    }

    public function deleteCustomer(\App\Models\User $user)
    {
        return $this->stripe->customers->delete($user->stripe_customer_id);
    }

    public function retrieveCustomer(\App\Models\User $user)
    {
        return $this->stripe->customers->retrieve($user->stripe_customer_id);
    }

    public function retrieveToken($tokenID)
    {
        return $this->stripe->tokens->retrieve($tokenID, []);
    }
    public function setWebhookEvent($payload, $sigHeader, $challenge)
    {
        try {
            $this->webhookEvent = \Stripe\Webhook::constructEvent($payload, $sigHeader, $challenge);
        } catch (\UnexpectedValueException | SignatureVerificationException $e){
            $this->webhookError = $e;
        }
    }

    public function hasAnyWebhookError(): bool
    {
        return $this->webhookError != null;
    }

    public function getWebhookError() : \Exception
    {
        return $this->webhookError;
    }

    public function getWebhookEvent() : Event
    {
       return $this->webhookEvent;
    }

    public function createPaymentMethod($data)
    {
       return $this->stripe->paymentMethods->create([
           'type' => 'card',
           'card' => [
               'token' => $data['token']
           ],
       ]);
    }

    public function retrievePaymentMethod($stripePaymentMethodID): PaymentMethod
    {
        return $this->stripe->paymentMethods->retrieve($stripePaymentMethodID, []);
    }

    public function updatePaymentMethod()
    {
    }

    public function deletePaymentMethod()
    {

    }


    public function createSetupIntent(Customer $stripeCustomer): \Stripe\SetupIntent
    {
        return $this->stripe->setupIntents->create([
            'customer' => $stripeCustomer->id,
            'usage' => 'off_session',
        ]);

    }
}
