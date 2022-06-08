<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRoleTable extends Migration
{
    public function up()
    {
      Schema::connection('mysql2')->create('role', function (Blueprint $table) {
        $table->increments('id');
        $table->string('name');
        $table->string('description');
        $table->timestamps();
      });
    }
    public function down()
    {
      Schema::dropIfExists('role');
    }
}
