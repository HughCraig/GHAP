<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatasetSubjectkeywordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('dataset_subject_keyword', function (Blueprint $table) {
            $table->unsignedBigInteger('dataset_id');
            $table->foreign('dataset_id')->references('id')->on('dataset')->onDelete('cascade');

            $table->unsignedBigInteger('subject_keyword_id');
            $table->foreign('subject_keyword_id')->references('id')->on('subject_keyword')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        Schema::connection('mysql2')->dropIfExists('dataset_subject_keyword');
    }
}
