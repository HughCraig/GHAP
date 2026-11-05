<?php

namespace Tests\V2\Sql;

use Illuminate\Support\Facades\DB;
use Tests\V2\TestCase;

class IndexesTest extends TestCase
{
    public function indexProvider()
    {
        return [
            'geom' => ['v2_dataitem_geom_gist', ['gist', '(geom)']],
            'title' => ['v2_dataitem_title_trgm', ['gin', 'gin_trgm_ops', '(title ']],
            'placename' => ['v2_dataitem_placename_trgm', ['gin', 'gin_trgm_ops', '(placename ']],
        ];
    }

    /**
     * @dataProvider indexProvider
     */
    public function testIndexExists($name, array $fragments)
    {
        $row = DB::selectOne(
            "select indexdef from pg_indexes where schemaname = 'tlcmap' and tablename = 'dataitem' and indexname = ?",
            [$name]
        );
        $this->assertNotNull($row, "Index $name is missing.");
        foreach ($fragments as $fragment) {
            $this->assertStringContainsString($fragment, $row->indexdef);
        }
        $valid = DB::selectOne(
            "select i.indisvalid from pg_index i join pg_class c on c.oid = i.indexrelid"
            . " join pg_namespace n on n.oid = c.relnamespace where n.nspname = 'tlcmap' and c.relname = ?",
            [$name]
        );
        $this->assertTrue($valid->indisvalid, "Index $name is not valid.");
    }

    /**
     * CONCURRENTLY, so writes to dataitem go on while the indexes build; PostgreSQL refuses it
     * inside a transaction.
     */
    public function testIndexesAreBuiltConcurrentlyOutsideATransaction()
    {
        $file = database_path('migrations/2026_11_10_000003_add_v2_indexes.php');
        require_once $file;
        $this->assertFalse((new \AddV2Indexes())->withinTransaction);

        $source = file_get_contents($file);
        $this->assertSame(3, substr_count($source, 'CREATE INDEX CONCURRENTLY IF NOT EXISTS'));
        $this->assertSame(3, substr_count($source, 'DROP INDEX CONCURRENTLY IF EXISTS'));
        $this->assertStringNotContainsString("'CREATE INDEX IF", $source);
    }
}
