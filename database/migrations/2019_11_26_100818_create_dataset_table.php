<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDatasetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tlcmap.dataset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->text('description');
            $table->boolean('public')->default(false);
            $table->boolean('allowanps')->default(false);
            $table->text('creator')->nullable();
            $table->text('publisher')->nullable();
            $table->text('contact')->nullable();
            $table->text('citation')->nullable();
            $table->text('doi')->nullable();
            $table->text('source_url')->nullable();
            $table->text('linkback')->nullable();
            $table->double('latitude_from')->nullable();
            $table->double('longitude_from')->nullable();
            $table->double('latitude_to')->nullable();
            $table->double('longitude_to')->nullable();
            $table->text('temporal_from')->nullable();
            $table->text('temporal_to')->nullable();
            $table->date('created')->nullable();
            $table->text('language')->nullable();
            $table->text('license')->nullable();
            $table->text('rights')->nullable();
            $table->text('kml_style')->nullable();
            $table->text('kml_journey')->nullable();
            $table->unsignedBigInteger('recordtype_id')->nullable()->default(0);
            $table->text('warning')->nullable();
            $table->timestamps();

//            Foreign key definitions if it's needed.
//            $table->foreign('recordtype_id')
//                ->references('id')
//                ->on('recordtype')
//                ->onDelete('SET NULL')
//                ->onUpdate('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tlcmap.dataset');
    }
}
