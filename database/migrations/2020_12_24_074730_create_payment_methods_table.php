<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentMethodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('payment_gateway_id');
            $table->string('token');
            $table->string('type')->nullable();
            $table->string('card_last4');
            $table->string('card_fingerprint')->unique(); // use to detect duplicate card entry
            $table->string('card_expiry_date');
            $table->string('card_issue_country');
            $table->boolean('is_primary')->default(false);

        $table->unique(['payment_gateway_id' , 'token']);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
}
