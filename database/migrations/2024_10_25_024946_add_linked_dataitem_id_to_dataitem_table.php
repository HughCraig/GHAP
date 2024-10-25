<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddLinkedDataitemIdToDataitemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->bigInteger('linked_dataitem_id')->nullable();
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
            $table->dropColumn('linked_dataitem_id');
        });
    }
}
