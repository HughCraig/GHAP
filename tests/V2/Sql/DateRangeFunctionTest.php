<?php

namespace Tests\V2\Sql;

use Illuminate\Support\Facades\DB;
use Tests\V2\TestCase;

/** v2_udate_range: both bounds from one parse, each exactly what v2_udate and v2_udate_end return. */
class DateRangeFunctionTest extends TestCase
{
    /** Every input DateFunctionsTest uses, valid and unparseable, blank included. */
    private function dateFunctionsTestInputs(): array
    {
        $cases = new DateFunctionsTest();
        $inputs = [];
        foreach ($cases->validProvider() as $case) {
            $inputs[] = $case[0];
        }
        foreach ($cases->nullProvider() as $case) {
            $inputs[] = $case[0];
        }
        // test_spec_reference_values_as_literals and test_safe_integer_boundary.
        foreach (['1970', '1856', '1856-03', '1856-03-04', '4/3/1856', '400', '1', '-1', '-400', '-51021',
                     '2000-02-29', '285000', '-283000', '-286000'] as $input) {
            $inputs[] = $input;
        }
        return $inputs;
    }

    /** @return array of [input, range start, range end, v2_udate, v2_udate_end] for the inputs */
    private function compare(array $inputs): array
    {
        $placeholders = implode(', ', array_fill(0, count($inputs), '(CAST(? AS text))'));
        return DB::select(
            "select x, (tlcmap.v2_udate_range(x))[1] as rs, (tlcmap.v2_udate_range(x))[2] as re,"
            . " tlcmap.v2_udate(x) as s, tlcmap.v2_udate_end(x) as e from (values $placeholders) as t(x)",
            $inputs
        );
    }

    public function test_range_equals_both_functions_over_every_date_functions_test_input()
    {
        $inputs = $this->dateFunctionsTestInputs();
        $rows = $this->compare($inputs);
        $this->assertCount(count($inputs), $rows);
        foreach ($rows as $row) {
            $this->assertSame([$row->s, $row->e], [$row->rs, $row->re], 'input [' . $row->x . ']');
        }
    }

    public function test_null_and_unparseable_input_give_a_null_range()
    {
        $row = DB::selectOne(
            "select tlcmap.v2_udate_range(null) as n, tlcmap.v2_udate_range('') as b,"
            . " tlcmap.v2_udate_range(E' \\t\\r\\n') as w, tlcmap.v2_udate_range('about 1856') as j"
        );
        $this->assertNull($row->n);
        $this->assertNull($row->b);
        $this->assertNull($row->w);
        $this->assertNull($row->j);
    }

    /**
     * Each year from 2001 BCE to 2401 CE in every form, and the years where a bound leaves the
     * safe-integer range, where the first instant can be inside it and the last outside.
     */
    public function test_range_equals_both_functions_over_generated_years_and_the_range_limits()
    {
        $mismatches = DB::selectOne(<<<'SQL'
with years as (
    select g from generate_series(-2001, 2401) g where g <> 0
    union all select g from generate_series(287380, 287410) g
    union all select g from generate_series(-283470, -283440) g
    union all select g from generate_series(9999990, 10000010) g
), inputs as (
    select g::text as x from years
    union all select lpad(abs(g)::text, 4, '0') || '-02' from years where g > 0
    union all select g::text || '-02-29' from years
    union all select g::text || '-12-31T23:59:59.999' from years
    union all select g::text || '-00-00' from years
    union all select '29/2/' || g::text from years where g between 1000 and 2401
    union all select '00/00/' || abs(g)::text from years
)
select count(*) as total,
       count(*) filter (where (tlcmap.v2_udate_range(x))[1] is distinct from tlcmap.v2_udate(x)
                           or (tlcmap.v2_udate_range(x))[2] is distinct from tlcmap.v2_udate_end(x)) as bad,
       count(*) filter (where tlcmap.v2_udate(x) is not null and tlcmap.v2_udate_end(x) is null) as split
from inputs
SQL
        );
        $this->assertGreaterThan(20000, (int) $mismatches->total);
        $this->assertSame(0, (int) $mismatches->bad);
        // The limit windows include a year whose first instant is safe and whose last is not.
        $this->assertGreaterThan(0, (int) $mismatches->split);
    }
}
