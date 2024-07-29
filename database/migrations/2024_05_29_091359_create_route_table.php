<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteTable extends Migration
{
    public function up()
    {
        Schema::create('tlcmap.route', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dataset_id')->index()->nullable();
            $table->text('title')->nullable();
            $table->text('description')->nullable();
            $table->integer('size')->nullable();
            $table->timestamps();

            // Cascade delete if it's needed.
            $table->foreign('dataset_id')->references('id')->on('tlcmap.dataset');
            //     ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tlcmap.route');
    }
}
