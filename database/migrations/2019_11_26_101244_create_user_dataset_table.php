<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDatasetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('pgsql2')->create('user_dataset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('dataset_id')->index();
            $table->text('dsrole');
            $table->timestamps();

//            Foreign key definitions if it's needed.
//            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
//            $table->foreign('dataset_id')->references('id')->on('dataset')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('pgsql2')->dropIfExists('user_dataset');
    }
}
