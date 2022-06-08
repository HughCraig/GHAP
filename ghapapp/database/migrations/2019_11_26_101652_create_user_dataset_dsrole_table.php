<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserDatasetDsroleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql2')->create('user_dataset_dsrole', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('user_dataset_id');
            $table->unsignedInteger('dsrole_id');
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
        Schema::dropIfExists('user_dataset_dsrole');
    }
}
