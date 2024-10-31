<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequirementsTable extends Migration
{
    public function up()
    {
        
        Schema::create('requirements', function (Blueprint $table) {
            $table->id('requirement_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

    
    }

    public function down()
    {
        Schema::dropIfExists('requirements');
    }
}
