<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->enum('status', [
                \App\Models\Payment::PENDING,
                \App\Models\Payment::FAILED,
                \App\Models\Payment::REQUIRES_ACTION,
                \App\Models\Payment::CANCELLED,
                \App\Models\Payment::REFUNDED,
                \App\Models\Payment::SUCCESS,
            ]);
            $table->text('payment_status_message')->nullable();
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('order_id');
            $table->string('tx_no')->unique();
            $table->string('checkout_id')->unique()->nullable();
            $table->string('refund_id')->unique()->nullable();
            $table->unsignedBigInteger('payment_method_id')->nullable();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->onDelete('set null');
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
        Schema::dropIfExists('payments');
    }
}
