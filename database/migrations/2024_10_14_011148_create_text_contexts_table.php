<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTextContextsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tlcmap.text_contexts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('dataitem_uid'); 
            $table->unsignedBigInteger('text_id'); 
            $table->unsignedBigInteger('start_index'); 
            $table->unsignedBigInteger('end_index'); 
            $table->unsignedBigInteger('sentence_start_index');
            $table->unsignedBigInteger('sentence_end_index'); 
            $table->unsignedBigInteger('line_index'); 
            $table->unsignedBigInteger('line_word_start_index'); 
            $table->unsignedBigInteger('line_word_end_index'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tlcmap.text_contexts');
    }
}
