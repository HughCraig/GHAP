<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;


class AddSTMetricsColumnsToDataitemTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        // Add geom and geog columns
        DB::statement('ALTER TABLE tlcmap.dataitem ADD COLUMN geom geometry(Point, 4326)');
        DB::statement('ALTER TABLE tlcmap.dataitem ADD COLUMN geog geography(Point, 4326)');

        // Populate geom and geog for existing data
        DB::statement('UPDATE tlcmap.dataitem SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)');
        DB::statement('UPDATE tlcmap.dataitem SET geog = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography');

        // Create a trigger to update geom and geog on insert or update
        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_geom_geog()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.geom := ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326);
                NEW.geog := ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326)::geography;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER update_geom_geog_trigger
            BEFORE INSERT OR UPDATE ON tlcmap.dataitem
            FOR EACH ROW EXECUTE FUNCTION update_geom_geog();
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop triggers
        DB::unprepared('
            DROP TRIGGER IF EXISTS update_geom_trigger ON tlcmap.dataitem;
            DROP FUNCTION IF EXISTS update_geom;
        ');

        Schema::table('tlcmap.dataitem', function (Blueprint $table) {
            // Drop geom and geog columns
            $table->dropColumn('geom');
            $table->dropColumn('geog');
        });
    }
}
