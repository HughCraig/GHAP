<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAnpsColumnsToDataitemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->string('uid', 100)->index()->nullable();
            $table->unsignedBigInteger('original_id')->index()->nullable();
            $table->unsignedBigInteger('datasource_id')->index()->default(1);
            $table->text('parish')->nullable();
            $table->text('flag')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->dropColumn(['original_id', 'datasource_id', 'parish', 'flag']);
        });
    }
}
