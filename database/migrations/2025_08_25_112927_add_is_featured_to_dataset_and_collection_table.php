<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFeaturedToDatasetTable extends Migration
{
   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->boolean('is_featured')->nullable();
        });

        Schema::table('tlcmap.collection', function (Blueprint $table) {
            $table->boolean('is_featured')->nullable(); 
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
            $table->dropColumn('is_featured');
        });

        Schema::table('tlcmap.collection', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
}
