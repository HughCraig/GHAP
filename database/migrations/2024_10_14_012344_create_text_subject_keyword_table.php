<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTextSubjectKeywordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tlcmap.text_subject_keyword', function (Blueprint $table) {
            $table->unsignedBigInteger('text_id');
            $table->unsignedBigInteger('subject_keyword_id');
    });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tlcmap.text_subject_keyword');
    }
}
