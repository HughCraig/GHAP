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
        Schema::create('tlcmap.role_user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('role_id')->index();
            $table->unsignedBigInteger('user_id')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tlcmap.role_user');
    }
}
