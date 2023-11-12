<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageColumnsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });

        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });

        Schema::table('tlcmap.collection', function (Blueprint $table) {
            $table->string('image_path')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });

        Schema::table('tlcmap.collection', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
}
