<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFeedbacksTable extends Migration
{
    public function up()
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id('feedback_id');
            $table->morphs('sender'); // Creates sender_id and sender_type columns
            $table->morphs('recipient'); // Creates recipient_id and recipient_type columns
            $table->text('comment');
            $table->decimal('rating', 8, 2);
            $table->unsignedBigInteger('ride_id');
            $table->timestamps();

            $table->foreign('ride_id')->references('ride_id')->on('ride_histories');
        });
    }

    public function down()
    {
        Schema::dropIfExists('feedbacks');
    }
}
