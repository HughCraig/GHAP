<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/*
    Pivot table for User and Role tables
    Used to map a user to a role (1 user has 1 role, 1 role has many users)
*/

class CreateRoleUserTable extends Migration
{
    public function up()
    {
      Schema::connection('mysql2')->create('role_user', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('role_id')->unsigned();
        $table->text('user_email');
      });
    }
    public function down()
    {
      Schema::dropIfExists('role_user');
    }
}
