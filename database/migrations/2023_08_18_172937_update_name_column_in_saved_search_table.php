<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateNameColumnInSavedSearchTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update all the null entries to 'Search'
        DB::table('tlcmap.saved_search')->whereNull('name')->update(['name' => 'Search']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tlcmap.saved_search', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });
    }
}
