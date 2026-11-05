<?php

namespace Tests\V2\Sql;

use Illuminate\Support\Facades\DB;
use Tests\V2\TestCase;

/** How the date functions are built, for speed; DateFunctionsTest holds what they compute. */
class DateFunctionShapeTest extends TestCase
{
    private function language(string $function): ?string
    {
        $row = DB::selectOne(
            'select l.lanname from pg_proc p join pg_namespace n on n.oid = p.pronamespace'
            . " join pg_language l on l.oid = p.prolang where n.nspname = 'tlcmap' and p.proname = ?",
            [$function]
        );
        return $row === null ? null : $row->lanname;
    }

    public function test_public_wrappers_are_sql_so_they_inline()
    {
        $this->assertSame('sql', $this->language('v2_udate'));
        $this->assertSame('sql', $this->language('v2_udate_end'));
        $this->assertSame('plpgsql', $this->language('v2_udate_parts'));
    }

    public function test_civil_day_arithmetic_is_inlined()
    {
        $this->assertNull($this->language('v2_civil_ms'));
    }

    public function test_wrappers_are_immutable_strict_and_parallel_safe()
    {
        $rows = DB::select(
            "select p.proname, p.provolatile, p.proisstrict, p.proparallel from pg_proc p"
            . " join pg_namespace n on n.oid = p.pronamespace where n.nspname = 'tlcmap'"
            . " and p.proname in ('v2_udate', 'v2_udate_end', 'v2_udate_parts') order by p.proname"
        );
        $this->assertCount(3, $rows);
        foreach ($rows as $row) {
            $this->assertSame(['i', true, 's'], [$row->provolatile, $row->proisstrict, $row->proparallel], $row->proname);
        }
    }
}
