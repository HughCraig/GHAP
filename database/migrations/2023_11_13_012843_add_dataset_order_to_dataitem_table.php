<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use TLCMap\Models\Dataset;

class AddDatasetOrderToDataitemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            $table->integer('dataset_order')->nullable();
        });

        // Set initial dataset_order values order by id.
        DB::transaction(function () {
            $datasets = Dataset::with(['dataitems' => function ($query) {
                $query->orderBy('id');
            }])->get();
        
            foreach ($datasets as $dataset) {
                foreach ($dataset->dataitems as $index => $dataitem) {
                    $dataitem->dataset_order = $index;
                    $dataitem->save();
                }
            }
        });
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tlcmap.dataitems', function (Blueprint $table) {
            $table->dropColumn('dataset_order');
        });
    }
}
