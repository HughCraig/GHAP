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
    }
}
