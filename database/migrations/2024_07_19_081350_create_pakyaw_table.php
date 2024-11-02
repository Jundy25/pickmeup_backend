<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePakyawTable extends Migration
{
    public function up()
    {
        Schema::create('pakyaw', function (Blueprint $table) {
            $table->id('pakyaw_id');
            $table->unsignedBigInteger('ride_id');
            $table->dateTime('ride_date');
            $table->integer('number_of_riders');
            $table->string('pickup_location');
            $table->string('dropoff_location');
            $table->string('description');
            $table->decimal('fare', 8, 2);
            $table->string('status');
            $table->timestamps();

            $table->foreign('ride_id')->references('ride_id')->on('ride_histories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pakyaw');
    }
}
