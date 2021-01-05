<?php

namespace App\Http\Controllers;

use App\Adapters\Stripe\StripePaymentAdapter;
use App\Repositories\PaymentMethodRepository;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Auth;
class PaymentMethodController extends Controller
{

    protected $paymentMethodRepository;
    protected $stripePaymentAdapter;
    public function __construct(PaymentMethodRepository $paymentMethodRepository, StripePaymentAdapter $stripePaymentAdapter)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->stripePaymentAdapter = $stripePaymentAdapter;
    }

    public function all()
    {
        $paymentMethods = $this->paymentMethodRepository->all();
        return response()->json(['payment_methods' => $paymentMethods->toArray()]);
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $validator = \Validator::make($request->toArray(), [
            'stripe_payment_method_id' => [
                'required',
            ],
        ]);
        $paymentMethod = $this->stripePaymentAdapter->retrievePaymentMethod($request->stripe_payment_method_id);

        if(is_null($paymentMethod)){
             $validator->getMessageBag()->add(
                 "stripe_payment_method_id",
                 "Unable to confirm with this stripe payment method id $request->stripe_payment_method_id"
             );
        }

        if($user->paymentMethods()->firstWhere('card_fingerprint' , $paymentMethod->card->fingerprint)){
            $validator->getMessageBag()->add(
                "stripe_payment_method_id",
                "The payment method is exists"
            );
        }

        if($validator->fails()){
            return response()->json($validator->errors()->toArray(), 422);
        }

        $paymentMethod = $this->paymentMethodRepository->create($request);

        return response()->json(['payment_method' => $paymentMethod->toArray()]);
    }

    public function createSetupIntent(Request $request)
    {
        $user = Auth::user();
        $setupIntent = $user->retrieveStripeSetupIntent();
        return response()->json(['setup_intent' => $setupIntent->toArray()]);
    }
}
