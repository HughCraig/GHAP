<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddV2ExtendedDataIndex extends Migration
{
    /**
     * Indexes for the pre-filter an extended_data clause applies before it parses the XML
     * (RecordQuery::prefilter): a trigram index on the raw text, for the substrings a match must
     * contain, and a partial index on the records whose names or values are not written as they
     * read, which every pre-filter lets through. The partial index's condition has the terms of
     * RecordQuery's VALUES_NOT_AS_WRITTEN, character for character, so the planner can prove that
     * each term of a pre-filter's fallback is answered by it; it holds no record of temp_tlcmap
     * (2026-11-05).
     *
     * CONCURRENTLY and outside a transaction, as in 2026_11_10_000003; a build that fails leaves an
     * invalid index, which IF NOT EXISTS would then skip: drop it before running this again.
     */
    public $withinTransaction = false;

    public function up()
    {
        DB::unprepared('CREATE INDEX CONCURRENTLY IF NOT EXISTS v2_dataitem_extended_data_trgm ON tlcmap.dataitem USING gin (extended_data gin_trgm_ops)');
        DB::unprepared(
            'CREATE INDEX CONCURRENTLY IF NOT EXISTS v2_dataitem_extended_data_unliteral ON tlcmap.dataitem (id)'
            . " WHERE extended_data LIKE '%&#%' OR extended_data LIKE '%<!DOCTYPE%'"
            . " OR replace(extended_data, '<value><![CDATA[', '') LIKE '%<value%'"
            . " OR replace(extended_data, ']]></value>', '') LIKE '%]]>%'"
        );
    }

    public function down()
    {
        DB::unprepared('DROP INDEX CONCURRENTLY IF EXISTS tlcmap.v2_dataitem_extended_data_unliteral');
        DB::unprepared('DROP INDEX CONCURRENTLY IF EXISTS tlcmap.v2_dataitem_extended_data_trgm');
    }
}
