<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Civil;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

class HarnessTest extends TestCase
{
    public function test_connection_is_the_test_database()
    {
        $this->assertSame('tlcmap_test', DB::connection()->getDatabaseName());
    }

    public function test_fixture_record_gets_geom_from_trigger()
    {
        $id = Fixtures::record(['lat' => -32.9, 'lon' => 151.1]);

        $row = DB::selectOne('select ST_X(geom) x, ST_Y(geom) y, uid from tlcmap.dataitem where id = ?', [$id]);

        $this->assertEquals(151.1, $row->x);
        $this->assertEquals(-32.9, $row->y);
        $this->assertSame('a' . dechex($id), $row->uid);
    }

    public function test_fixture_ids_restart_each_test()
    {
        $this->assertSame(900001, Fixtures::layer());
        $this->assertSame(900002, Fixtures::layer());
    }

    public function test_civil_reference_values()
    {
        $this->assertSame(0, Civil::udate(1970));
        $this->assertSame(-3597523200000, Civil::udate(1856));
        $this->assertSame(-3592080000000, Civil::udate(1856, 3, 4));
        $this->assertSame(-74758377600000, Civil::udate(-399));
        $this->assertSame(-1672202880000000, Civil::udate(-51020));
        $this->assertSame(951782400000, Civil::udate(2000, 2, 29));
    }
}
