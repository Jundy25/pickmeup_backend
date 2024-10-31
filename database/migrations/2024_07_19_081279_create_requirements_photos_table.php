<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequirementsPhotosTable extends Migration
{
    public function up()
    {
        Schema::create('requirement_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id');
            $table->unsignedBigInteger('requirement_id');
            $table->string('photo_url', 2083);
            $table->timestamps();

            $table->foreign('rider_id')->references('rider_id')->on('riders')->onDelete('cascade');
            $table->foreign('requirement_id')->references('requirement_id')->on('requirements')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('requirement_photos');
    }
}
