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
        Schema::connection('mysql2')->create('user_dataset', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('user_email')->index();
            $table->unsignedBigInteger('dataset_id')->index();
            $table->text('dsrole');
            $table->timestamps();

            $table->foreign('user_email')->references('email')->on('user')->onDelete('cascade');
            $table->foreign('dataset_id')->references('id')->on('dataset')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_dataset');
    }
}
