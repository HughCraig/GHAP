<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIsFeaturedToDatasetAndCollectionTable extends Migration
{
   
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->string('featured_url')->nullable();
        });

        Schema::table('tlcmap.collection', function (Blueprint $table) {
            $table->string('featured_url')->nullable();
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
