<?php
namespace App\Repositories;

use App\Adapters\Stripe\StripePaymentAdapter;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Stripe\Exception\ApiErrorException;
use function GuzzleHttp\Psr7\str;

class PaymentMethodRepository implements \App\Interfaces\PaymentMethodRepositoryInterface {

    protected $user;
    protected $stripe;

    public function __construct()
    {
        $this->user = Auth::user();
        $this->stripe = new StripePaymentAdapter();
    }

    public function all() : Collection {
        return $this->user->paymentMethods()->with(['paymentGateway'])->get();
    }

    public function retrieve($paymentMethodID) : ?PaymentMethod {
        return $this->user->paymentMethods()->with(['paymentGateway'])->find($paymentMethodID);
    }

    public function create(Request $request)
    {
        $stripe_payment_method_id = $request->input('stripe_payment_method_id');
        $paymentGateway = PaymentGateway::firstWhere('name','stripe');

        $stripePaymentMethod = $this->stripe->retrievePaymentMethod($stripe_payment_method_id);

        $paymentMethod = $this->user->paymentMethods()->firstOrCreate([
            'card_fingerprint' => $stripePaymentMethod->card->fingerprint,
            'user_id' => $this->user->id,
        ],[
            'payment_gateway_id' => $paymentGateway->id,
            'token' => $stripePaymentMethod->id,
            'card_last4' => $stripePaymentMethod->card->last4,
            'type' => $stripePaymentMethod->card->brand,
            'card_expiry_date' => $stripePaymentMethod->card->exp_month . '/' . $stripePaymentMethod->card->exp_year,
            'card_issue_country' => $stripePaymentMethod->card->country,
        ]);

        // attach the payment method to customer
        $stripePaymentMethod->attach([
            'customer' => $this->user->retrieveStripeCustomerAccount()->id
        ]);

        $this->user->presetPrimaryPaymentMethod();
        if($request->input('set_primary') === true){
            $this->user->setPrimaryPaymentMethod($paymentMethod->id);
        }
        $this->user->stripe_customer_setup_intent_id = null;
        $this->user->save();
        return $paymentMethod;
    }

    public function createSetupIntent(): \Stripe\SetupIntent
    {
        // create a setup intent session
        return $this->user->retrieveStripeSetupIntent();
    }


}
