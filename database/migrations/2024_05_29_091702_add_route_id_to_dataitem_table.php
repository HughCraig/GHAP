<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRouteIdToDataitemTable extends Migration
{
    public function up()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->unsignedBigInteger('route_id')->nullable();
            $table->foreign('route_id')
                ->references('id')->on('tlcmap.route');
        });
    }

    public function down()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->dropForeign(['route_id']);
            $table->dropColumn('route_id');
        });
    }
}
