<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddV2Indexes extends Migration
{
    // Not CONCURRENTLY: Laravel runs PostgreSQL migrations inside a transaction.
    public function up()
    {
        DB::unprepared('CREATE INDEX IF NOT EXISTS v2_dataitem_geom_gist ON tlcmap.dataitem USING gist (geom)');
        DB::unprepared('CREATE INDEX IF NOT EXISTS v2_dataitem_title_trgm ON tlcmap.dataitem USING gin (title gin_trgm_ops)');
        DB::unprepared('CREATE INDEX IF NOT EXISTS v2_dataitem_placename_trgm ON tlcmap.dataitem USING gin (placename gin_trgm_ops)');
    }

    public function down()
    {
        DB::unprepared('DROP INDEX IF EXISTS tlcmap.v2_dataitem_placename_trgm');
        DB::unprepared('DROP INDEX IF EXISTS tlcmap.v2_dataitem_title_trgm');
        DB::unprepared('DROP INDEX IF EXISTS tlcmap.v2_dataitem_geom_gist');
    }
}
