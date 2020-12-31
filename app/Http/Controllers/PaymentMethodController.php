<?php

namespace App\Http\Controllers;

use App\Repositories\PaymentMethodRepository;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{

    protected $paymentMethodRepository;
    public function __construct(PaymentMethodRepository $paymentMethodRepository)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    public function all()
    {
        $paymentMethods = $this->paymentMethodRepository->all();
        return response()->json(['payment_methods' => $paymentMethods->toArray()]);
    }

    public function create(Request $request)
    {
        $paymentMethod = $this->paymentMethodRepository->create($request);

        return response()->json(['payment_method' => $paymentMethod->toArray()]);
    }

    public function createSetupIntent(Request $request)
    {
        $setupIntent = $this->paymentMethodRepository->createSetupIntent();

        return response()->json(['setup_intent' => $setupIntent->toArray()]);
    }
}
