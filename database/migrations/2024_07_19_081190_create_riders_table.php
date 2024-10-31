<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRidersTable extends Migration
{
    public function up()
    {
        Schema::create('riders', function (Blueprint $table) {
            $table->id('rider_id');
            $table->unsignedBigInteger('user_id');
            $table->date('registration_date');
            $table->text('verification_status');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rider_registrations');
    }
}
