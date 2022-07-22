<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCollectionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql2')->create('collection', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('name');
            $table->text('description')->nullable();
            $table->text('warning')->nullable();
            $table->unsignedBigInteger('owner');
            $table->boolean('public')->default(false);
            $table->text('creator')->nullable();
            $table->text('publisher')->nullable();
            $table->text('contact')->nullable();
            $table->text('citation')->nullable();
            $table->text('doi')->nullable();
            $table->text('source_url')->nullable();
            $table->text('language')->nullable();
            $table->double('latitude_from')->nullable();
            $table->double('longitude_from')->nullable();
            $table->double('latitude_to')->nullable();
            $table->double('longitude_to')->nullable();
            $table->text('temporal_from')->nullable();
            $table->text('temporal_to')->nullable();
            $table->text('license')->nullable();
            $table->text('rights')->nullable();
            $table->date('created')->nullable();
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
        Schema::connection('pgsql2')->dropIfExists('collection');
    }
}
