<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDataitemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tlcmap.dataitem', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dataset_id')->index()->nullable();
            $table->text('title');
            $table->unsignedBigInteger('recordtype_id')->index()->nullable()->default(1);
            $table->text('description')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->text('datestart')->nullable();
            $table->text('dateend')->nullable();
            $table->text('state')->nullable();
            $table->text('feature_term')->nullable();
            $table->text('lga')->nullable();
            $table->text('source')->nullable();
            $table->text('external_url')->nullable();
            $table->text('extended_data')->nullable();
            $table->text('kml_style_url')->nullable();
            $table->text('placename')->nullable();
            $table->timestamps();

//            Foreign key definitions if it's needed.
//            $table->foreign('recordtype_id')
//                ->references('id')
//                ->on('recordtype')
//                ->onDelete('SET NULL')
//                ->onUpdate('RESTRICT');
//            $table->foreign('dataset_id')
//                ->references('id')
//                ->on('dataset')
//                ->onDelete('CASCADE')
//                ->onUpdate('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tlcmap.dataitem');
    }
}
