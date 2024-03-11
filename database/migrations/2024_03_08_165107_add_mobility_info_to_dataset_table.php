<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use TLCMap\Models\Dataset;

class AddMobilityInfoToDatasetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->boolean('has_quantity')->nullable();
            $table->boolean('has_route')->nullable();
        });

        // Initialize mobility information for each dataset.
        DB::transaction(function () {
            $datasets = Dataset::with(['dataitems' => function ($query) {
                $query;
            }])->get();

            foreach ($datasets as $dataset) {
                $hasQuantity = false;
                $hasRoute = false;
                foreach ($dataset->dataitems as $dataitem) {
                    if (!empty($dataitem->quantity)) {
                        $hasQuantity = true;
                    }
                    if (!empty($dataitem->route_id)) {
                        $hasRoute = true;
                    }
                    if ($hasQuantity && $hasRoute) {
                        break;
                    }
                }
                $dataset->has_quantity = $hasQuantity;
                $dataset->has_route = $hasRoute;
                $dataset->save();
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
        Schema::table('tlcmap.dataset', function (Blueprint $table) {
            $table->dropColumn('has_quantity');
            $table->dropColumn('has_route');
        });
    }
}
