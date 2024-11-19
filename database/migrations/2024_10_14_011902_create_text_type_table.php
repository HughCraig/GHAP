<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateTextTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tlcmap.texttype', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 100);
            $table->text('description')->nullable();
        });

        // Insert predefined types
        DB::table('tlcmap.texttype')->insert([
            ['type' => 'Novel', 'description' => ''],
            ['type' => 'Essay', 'description' => ''],
            ['type' => 'Report','description' => ''],
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tlcmap.texttype');
    }
}
