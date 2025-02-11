<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateTextTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('tlcmap.texttype');
        Schema::create('tlcmap.texttype', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type', 100);
            $table->text('description')->nullable();
        });

        // Insert predefined types
        DB::table('tlcmap.texttype')->insert([
            ['type' => 'Fiction', 'description' => ''],
            ['type' => 'Fiction (collection)', 'description' => ''],
            ['type' => 'Non-fiction','description' => ''],
            ['type' => 'Non-fiction (collection)','description' => ''],
            ['type' => 'Other','description' => ''],
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
