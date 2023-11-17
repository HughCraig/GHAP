<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLockedRoleToRoleTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('tlcmap.role')->insert([
            'name' => 'LOCKED',
            'description' => 'Locked users. Restrict all actions',
            'created_at' => null,
            'updated_at' => null
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('tlcmap.role')->where('name', 'LOCKED')->delete();
    }
}
