<?php

namespace App\Interfaces;

use App\Models\User;
use Illuminate\Http\Request;

interface PaymentMethodRepositoryInterface {

    public function all();

    public function retrieve($paymentMethodID);
    public function create(Request $request);

    public function createSetupIntent();
}
