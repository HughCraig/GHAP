<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TLCMap\Http\Helpers\GeneralFunctions;

class AddUdatestartAndUdateendToDataitemTable extends Migration
{
    public function up()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->bigInteger('udatestart')->nullable();  
            $table->bigInteger('udateend')->nullable();  
        });

        $dataitems = DB::table('tlcmap.dataitem')->get();

        foreach ($dataitems as $i) {
            $proppairs = [];

            if (!empty($i->datestart)) {
                $proppairs["udatestart"] = GeneralFunctions::dataToUnixtimestamp($i->datestart);
            }
            if (!empty($i->dateend)) {
                $proppairs["udateend"] = GeneralFunctions::dataToUnixtimestamp($i->dateend);
            }

            // Only update if there are values to update
            if (!empty($proppairs)) {
                DB::table('tlcmap.dataitem')->where('id', $i->id)->update($proppairs);
            }
        }
    }

    public function down()
    {
        // Drop the columns if the migration is rolled back
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->dropColumn('udatestart');
            $table->dropColumn('udateend');
        });
    }
}