<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRideLocationsTable extends Migration
{
    public function up()
    {
        Schema::create('ride_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_id');
            $table->decimal('rider_latitude', 10, 8)->nullable();
            $table->decimal('rider_longitude', 11, 8)->nullable();
            $table->decimal('customer_latitude', 10, 8);
            $table->decimal('customer_longitude', 11, 8);
            $table->decimal('dropoff_latitude', 11, 8);
            $table->decimal('dropoff_longitude', 11, 8);
            $table->timestamps();
            // Foreign key to reference users table
            $table->foreign('ride_id')->references('ride_id')->on('ride_histories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ride_locations');
    }
}
