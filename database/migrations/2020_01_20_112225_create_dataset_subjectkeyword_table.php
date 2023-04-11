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
        Schema::create('tlcmap.dataset_subject_keyword', function (Blueprint $table) {
            $table->unsignedBigInteger('dataset_id')->index();
            $table->unsignedBigInteger('subject_keyword_id')->index();

//            Foreign key definitions if it's needed.
//            $table->foreign('dataset_id')->references('id')->on('dataset')->onDelete('cascade');
//            $table->foreign('subject_keyword_id')->references('id')->on('subject_keyword')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tlcmap.dataset_subject_keyword');
    }
}
