<?php

namespace Tests\V2\Sql;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Civil;
use Tests\V2\TestCase;

class DateFunctionsTest extends TestCase
{
    public function validProvider()
    {
        $d1856 = [Civil::udate(1856, 3, 4), Civil::udate(1856, 3, 5) - 1];
        $t = Civil::udate(1856, 3, 4) + 52200000;
        $tf = Civil::udate(1856, 3, 4) + 52215123;

        return [
            '1970' => ['1970', Civil::udate(1970), Civil::udate(1971) - 1],
            '1856' => ['1856', Civil::udate(1856), Civil::udate(1857) - 1],
            '400' => ['400', Civil::udate(400), Civil::udate(401) - 1],
            '0001' => ['0001', Civil::udate(1), Civil::udate(2) - 1],
            '-1' => ['-1', Civil::udate(0), Civil::udate(1) - 1],
            '-400' => ['-400', Civil::udate(-399), Civil::udate(-398) - 1],
            '-51021' => ['-51021', Civil::udate(-51020), Civil::udate(-51019) - 1],
            '1856-03' => ['1856-03', Civil::udate(1856, 3), Civil::udate(1856, 4) - 1],
            '1856-12' => ['1856-12', Civil::udate(1856, 12), Civil::udate(1857) - 1],
            '-400-02' => ['-400-02', Civil::udate(-399, 2), Civil::udate(-399, 3) - 1],
            '1856-03-04' => ['1856-03-04', $d1856[0], $d1856[1]],
            '2000-02-29' => ['2000-02-29', Civil::udate(2000, 2, 29), Civil::udate(2000, 3, 1) - 1],
            '4/3/1856' => ['4/3/1856', $d1856[0], $d1856[1]],
            '04/03/1856' => ['04/03/1856', $d1856[0], $d1856[1]],
            '1856-00-00' => ['1856-00-00', Civil::udate(1856), Civil::udate(1857) - 1],
            '00/00/1856' => ['00/00/1856', Civil::udate(1856), Civil::udate(1857) - 1],
            '1856-03-04T14:30' => ['1856-03-04T14:30', $t, $t],
            '1856-03-04T14:30:15.1239' => ['1856-03-04T14:30:15.1239', $tf, $tf],
            'padded' => ['  1856  ', Civil::udate(1856), Civil::udate(1857) - 1],
            'bce leap day' => ['-5-02-29', Civil::udate(-4, 2, 29), Civil::udate(-4, 3, 1) - 1],
            'bce year-month-00-00' => ['-400-00-00', Civil::udate(-399), Civil::udate(-398) - 1],
            'fraction padded' => ['1856-03-04T14:30:15.5', Civil::udate(1856, 3, 4) + 52215500, Civil::udate(1856, 3, 4) + 52215500],
        ];
    }

    /**
     * @dataProvider validProvider
     */
    public function test_valid_inputs($in, $start, $end)
    {
        $r = DB::selectOne('select tlcmap.v2_udate(?) s, tlcmap.v2_udate_end(?) e', [$in, $in]);
        $this->assertSame($start, (int) $r->s);
        $this->assertSame($end, (int) $r->e);
    }

    public function nullProvider()
    {
        $inputs = ['', '   ', '0', '-0', '0000', '1856-13', '1856-04-31', '1900-02-29', '-16112-11-31',
            '1856-03-04T24:00', '1856-03-04T12:60', '13/13/1856', '1856/03/04', 'about 1856', 'c.1856',
            str_repeat('9', 30), '300000', '00/00/0000', '0000-00-00', '1856-03-04T12:30:60', '1/1/856',
            '-1856-03-04T', '8000000'];
        $out = [];
        foreach ($inputs as $i) {
            $out['[' . $i . ']'] = [$i];
        }

        return $out;
    }

    /**
     * @dataProvider nullProvider
     */
    public function test_unparseable_inputs_are_null($in)
    {
        $r = DB::selectOne('select tlcmap.v2_udate(?) s, tlcmap.v2_udate_end(?) e', [$in, $in]);
        $this->assertNull($r->s);
        $this->assertNull($r->e);
    }

    public function test_safe_integer_boundary()
    {
        // 285,000 CE and 283,000 BCE are inside the range; 286,000 BCE is outside.
        $r = DB::selectOne("select tlcmap.v2_udate('285000') s, tlcmap.v2_udate('-283000') n, tlcmap.v2_udate('-286000') o");
        $this->assertSame(Civil::udate(285000), (int) $r->s);
        $this->assertSame(Civil::udate(-282999), (int) $r->n);
        $this->assertNull($r->o);
    }

    public function test_null_in_null_out()
    {
        $r = DB::selectOne('select tlcmap.v2_udate(null) s, tlcmap.v2_udate_end(null) e');
        $this->assertNull($r->s);
        $this->assertNull($r->e);
    }

    public function test_functions_are_immutable()
    {
        $rows = DB::select(
            "select p.proname, p.provolatile, p.proparallel, p.proisstrict from pg_proc p
             join pg_namespace n on n.oid = p.pronamespace
             where n.nspname = 'tlcmap' and p.proname in ('v2_udate', 'v2_udate_end')"
        );
        $this->assertCount(2, $rows);
        foreach ($rows as $row) {
            $this->assertSame('i', $row->provolatile);
            $this->assertSame('s', $row->proparallel);
            $this->assertTrue((bool) $row->proisstrict);
        }
    }
}
