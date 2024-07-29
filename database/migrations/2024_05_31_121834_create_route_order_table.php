<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteOrderTable extends Migration
{
    public function up()
    {
        Schema::create('tlcmap.route_order', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('route_id');
            $table->unsignedBigInteger('dataitem_id');
            $table->unsignedBigInteger('position');

            $table->foreign('route_id')->references('id')->on('tlcmap.route')->onDelete('cascade');
            $table->foreign('dataitem_id')->references('id')->on('tlcmap.dataitem')->onDelete('cascade');
            $table->unique('dataitem_id');
            $table->index(['route_id', 'dataitem_id']);
            $table->index(['route_id', 'position']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('tlcmap.route_order');
    }
}
