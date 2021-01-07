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
        $sig_header = $request->header('Stripe-Signature');
        Log::debug($sig_header);
        $this->stripe->setWebhookEvent(
            // the content payload send from the stripe
            $request->getContent(),
            $sig_header, // signature from stripe
            config('services.stripe.webhook_secret') // our webhook secret loaded from services.php and proxy from .env
        );

        if($this->stripe->hasAnyWebhookError()){ // check is there any webhook error
            $error = $this->stripe->getWebhookError()->getMessage();
            Log::error("Stripe Webhook Error: $error");
            return response('Webhook Challenge Error', 400);
        }

        $event = $this->stripe->getWebhookEvent(); // retrieve the webhook event object


        switch($event->type){ // forward different event to different event handler
            case 'payment_intent.succeeded':
                return $this->handlePaymentSuccess($event->data->object);
            case 'payment_intent.payment_failed':
                return $this->handlePaymentFailed($event->data->object);
            case 'payment_intent.requires_action':
                return $this->handlePaymentRequireActions($event->data->object);
        }

    }

    private function handlePaymentSuccess(PaymentIntent $paymentIntent){
        // find the payment transaction number
        $payment = Payment::firstWhere('tx_no', $paymentIntent->id);

        // update the payment status to success
        $payment->update([
            'status' => Payment::SUCCESS,
        ]);

        return response()->json(['success' => true]);
    }

    public function handlePaymentFailed(PaymentIntent $paymentIntent)
    {
        $payment = Payment::firstWhere('tx_no', $paymentIntent->id);
        // update the payment status to success
        $payment->update([
            'status' => Payment::FAILED,
        ]);

        return response()->json(['success' => true]);
    }

    public function handlePaymentRequireActions(PaymentIntent $paymentIntent)
    {
        $payment = Payment::firstWhere('tx_no', $paymentIntent->id);
        // update the payment status to success
        $payment->update([
            'status' => Payment::REQUIRES_ACTION,
        ]);

        return response()->json(['success' => true]);
    }


}
