<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRouteColumnsToDataitemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->bigInteger('route_id')->index()->nullable();
            $table->string('route_original_id', 100)->nullable();
            $table->string('route_title', 100)->nullable();
            $table->integer('stop_idx')->index()->nullable();
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
            $table->dropColumn(['route_id']);
            $table->dropColumn(['route_original_id']);
            $table->dropColumn(['route_title']);
            $table->dropColumn(['stop_idx']);
        });
    }
}
