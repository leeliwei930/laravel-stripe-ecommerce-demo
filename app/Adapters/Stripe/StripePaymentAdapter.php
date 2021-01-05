<?php

namespace App\Adapters\Stripe;

use App\Adapters\ManagePaymentMethodsAdapter;
use App\Adapters\PaymentAdapter;
use App\Adapters\ManageCustomer;
use App\Adapters\PaymentWebhookAdapter;

use App\Adapters\SavePaymentDetails;
use App\Traits\ErrorRecorder;
use Cassandra\Custom;
use Stripe\Customer;
use Stripe\Event;

use App\Models\User;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;

use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\SetupIntent;

class StripePaymentAdapter implements PaymentAdapter, ManageCustomer, PaymentWebhookAdapter, ManagePaymentMethodsAdapter, SavePaymentDetails {
    use ErrorRecorder;
    protected $webhookEvent;
    protected $webhookError = null;
    protected $stripe;
    protected $error;

    public function __construct()
    {
        $this->stripe = new \Stripe\StripeClient(config('services.stripe.key'));
    }

    public function createPaymentIntent($paymentIntentData): ?\Stripe\PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->create([
                'customer' => $paymentIntentData['customer'],
                'description' => $paymentIntentData['description'] ?? "",
                'payment_method' => $paymentIntentData['payment_method'],
                'amount' => $paymentIntentData['amount'],
                'currency' => $paymentIntentData['currency'],
                'confirm' => $paymentIntentData['confirm'],
                'confirmation_method' => $paymentIntentData['confirmation_method']
            ]);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function retrievePaymentIntent($paymentIntentID): ?\Stripe\PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->retrieve($paymentIntentID);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function confirmPaymentIntent($paymentIntentID, $stripePaymentMethodID)
    {
        // TODO: Implement confirmPaymentIntent() method.
    }

    public function cancelPaymentIntent($paymentIntentID): ?PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->cancel($paymentIntentID);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function updatePaymentIntent($paymentIntentID, $paymentIntentData): ?PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->update($paymentIntentID, [
                'amount' => $paymentIntentData['amount'],
                'currency' => $paymentIntentData['currency']
            ]);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function createCustomer(\App\Models\User $user) : ?Customer
    {
        try {
        return $this->stripe->customers->create([
            'name' => $user->name,
            'email' => $user->email
        ]);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function updateCustomer(\App\Models\User $user) : ?Customer
    {
        try {
            return $this->stripe->customers->update($user->stripe_customer_id, [
                'name' => $user->name,
                'email' => $user->email
            ]);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return  null;
    }

    public function deleteCustomer(\App\Models\User $user)
    {
        try {
            return $this->stripe->customers->delete($user->stripe_customer_id);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function retrieveCustomer(\App\Models\User $user)
    {
        try {
            return $this->stripe->customers->retrieve($user->stripe_customer_id);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
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

    public function createPaymentMethod($data) : ?PaymentMethod
    {
    try {
       return $this->stripe->paymentMethods->create([
           'type' => 'card',
           'card' => [
               'token' => $data['token']
           ],
       ]);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function retrievePaymentMethod($stripePaymentMethodID): ?PaymentMethod
    {
        try {
            return $this->stripe->paymentMethods->retrieve($stripePaymentMethodID, []);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function updatePaymentMethod()
    {
    }

    public function deletePaymentMethod()
    {

    }


    public function createSetupIntent($customerID): ?\Stripe\SetupIntent
    {
        try {
        return $this->stripe->setupIntents->create([
            'payment_method_types' => ['card'],
            'customer' => $customerID,
            'usage' => 'off_session',
        ]);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
        return null;
    }

    public function retrieveSetupIntent($setupIntentID) : ?SetupIntent
    {
        try {
        return $this->stripe->setupIntents->retrieve($setupIntentID);
        } catch (ApiErrorException $stripeException) {
            $this->recordError($stripeException);
        }
    }

}
