<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCollectionSubjectKeywordTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql2')->create('collection_subject_keyword', function (Blueprint $table) {
            $table->unsignedBigInteger('collection_id');
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
        Schema::connection('pgsql2')->dropIfExists('collection_subject_keyword');
    }
}
