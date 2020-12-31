<?php

namespace App\Adapters;
use App\Models\User;

interface  ManageCustomer {

    public function createCustomer(User $user);

    public function updateCustomer(User $user);

    public function deleteCustomer(User $user);

    public function retrieveCustomer(User $user);
}
