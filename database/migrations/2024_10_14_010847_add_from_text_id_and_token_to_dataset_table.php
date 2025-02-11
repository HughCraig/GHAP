<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFromTextIdAndTokenToDatasetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->unsignedBigInteger('from_text_id')->nullable()->default(null); 
            $table->string('access_token', 255)->nullable();
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
            $table->dropColumn('from_text_id'); 
            $table->dropColumn('access_token');
        });
    }
}
