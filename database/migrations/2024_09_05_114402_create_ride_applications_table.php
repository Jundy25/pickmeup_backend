<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ride_applications', function (Blueprint $table) {
            $table->id('apply_id');
            $table->unsignedBigInteger('ride_id');
            $table->integer('applier');
            $table->dateTime('date');
            $table->integer('apply_to');
            $table->string('status');
            $table->timestamps();

            $table->foreign('ride_id')->references('ride_id')->on('ride_histories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ride_applications');
    }
};
