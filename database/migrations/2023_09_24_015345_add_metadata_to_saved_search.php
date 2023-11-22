<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMetadataToSavedSearch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tlcmap.saved_search', function (Blueprint $table) {
            $table->text('description')->nullable(false)->default('Search');

            //Search Type
            $table->unsignedBigInteger('recordtype_id')->nullable()->default(0);

            //Content Warning
            $table->text('warning')->nullable();

            //Spatial_Coverage
            $table->double('latitude_from')->nullable();
            $table->double('longitude_from')->nullable();
            $table->double('latitude_to')->nullable();
            $table->double('longitude_to')->nullable();

            //Temporal_Coverage
            $table->text('temporal_from')->nullable();
            $table->text('temporal_to')->nullable();
        });


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tlcmap.saved_search', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'recordtype_id',
                'warning',
                'latitude_from',
                'longitude_from',
                'latitude_to',
                'longitude_to',
                'temporal_from',
                'temporal_to'
            ]);
        });
    }
}
