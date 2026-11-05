<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddV2Indexes extends Migration
{
    /**
     * CONCURRENTLY, so writes to tlcmap.dataitem are not blocked while the indexes build. PostgreSQL
     * refuses it inside a transaction, so this migration runs outside one. A build that fails leaves
     * an invalid index, which IF NOT EXISTS would then skip: drop it before running this again.
     */
    public $withinTransaction = false;

    public function up()
    {
        DB::unprepared('CREATE INDEX CONCURRENTLY IF NOT EXISTS v2_dataitem_geom_gist ON tlcmap.dataitem USING gist (geom)');
        DB::unprepared('CREATE INDEX CONCURRENTLY IF NOT EXISTS v2_dataitem_title_trgm ON tlcmap.dataitem USING gin (title gin_trgm_ops)');
        DB::unprepared('CREATE INDEX CONCURRENTLY IF NOT EXISTS v2_dataitem_placename_trgm ON tlcmap.dataitem USING gin (placename gin_trgm_ops)');
    }

    public function down()
    {
        DB::unprepared('DROP INDEX CONCURRENTLY IF EXISTS tlcmap.v2_dataitem_placename_trgm');
        DB::unprepared('DROP INDEX CONCURRENTLY IF EXISTS tlcmap.v2_dataitem_title_trgm');
        DB::unprepared('DROP INDEX CONCURRENTLY IF EXISTS tlcmap.v2_dataitem_geom_gist');
    }
}
