<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id('payment_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rider_id');
            $table->decimal('amount', 8, 2);
            $table->date('date');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('rider_id')->references('rider_id')->on('riders');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
