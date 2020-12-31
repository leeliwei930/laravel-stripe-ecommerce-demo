<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
         \App\Models\User::factory(3)->create();
         Product::factory(5)->create();
         PaymentGateway::create([
             'name' => 'stripe'
         ]);
    }
}
