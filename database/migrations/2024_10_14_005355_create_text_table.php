<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTextTable extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tlcmap.text', function (Blueprint $table) {
            $table->bigIncrements('id'); 
           
            $table->string('name', 255); 
            $table->unsignedBigInteger('texttype_id')->nullable()->default(0);
            $table->text('creator')->nullable();

            $table->text('publisher')->nullable();
            $table->text('contact')->nullable();
            $table->text('doi')->nullable();
            $table->text('source_url')->nullable();
            $table->text('linkback')->nullable();

            $table->text('content'); // text content

            $table->text('language')->nullable();
            $table->text('license')->nullable();
            $table->string('image_path')->nullable();

            $table->text('temporal_from')->nullable();
            $table->text('temporal_to')->nullable();
            $table->date('created')->nullable();
           
            $table->text('description')->nullable(); 
            $table->text('warning')->nullable();
            $table->text('citation')->nullable();
            $table->text('rights')->nullable();

            $table->string('access_token', 255);
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
        Schema::dropIfExists('tlcmap.text');
    }
}
