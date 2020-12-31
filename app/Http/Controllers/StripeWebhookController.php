<?php

namespace App\Http\Controllers;

use App\Adapters\Stripe\StripePaymentAdapter;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Event;
use Stripe\PaymentIntent;
use Stripe\StripeObject;

class StripeWebhookController extends Controller
{
    protected $stripe;
    public function __construct()
    {
        $this->stripe = new StripePaymentAdapter();
    }

    public function __invoke(Request $request)
    {
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        Log::debug($sig_header);
        $this->stripe->setWebhookEvent(
            $request->getContent(),
            $sig_header,
            config('services.stripe.webhook_secret')
        );

        if($this->stripe->hasAnyWebhookError()){
            $error = $this->stripe->getWebhookError()->getMessage();
            Log::error("Stripe Webhook Error: $error");
            return response('Webhook Challenge Error', 400);
        }

        $event = $this->stripe->getWebhookEvent();

        switch($event->type){
            case 'payment_intent.succeeded':
                Log::debug("PAYMENT SUCCESS: ".$event->data->toJSON());
                $this->handlePaymentSuccess($event->data->object);
                return;

        }

    }

    private function handlePaymentSuccess(PaymentIntent $paymentIntent){
        $payment = Payment::where('tx_no', $paymentIntent->id)->first();

        $paymentMethod = PaymentMethod::where('token', $paymentIntent->payment_method)->first();

        $payment->update([
            'status' => Payment::SUCCESS,
            'payment_method_id' => $paymentMethod->id
        ]);

        return response()->json(['success' => true]);
    }


}
