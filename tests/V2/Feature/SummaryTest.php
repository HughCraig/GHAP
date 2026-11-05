<?php

namespace Tests\V2\Feature;

use Illuminate\Http\Request;
use Tests\V2\Support\Civil;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\RecordQuery;
use TLCMap\Api\V2\Query\Summary;

class SummaryTest extends TestCase
{
    private const ALLOWED = ['sources', 'layers', 'name'];

    private function builder(array $params = [])
    {
        $request = Request::create('/api/v2/test?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        return RecordQuery::build(RecordFilters::fromParams(ParameterSet::fromRequest($request, self::ALLOWED)));
    }

    /** Four layers: warning null, "None", an advisory (with surrounding space), whitespace only. */
    private function seedFixture(): array
    {
        $a = Fixtures::layer(['name' => 'Alpha', 'warning' => null]);
        $b = Fixtures::layer(['name' => 'Beta', 'warning' => 'None']);
        $c = Fixtures::layer(['name' => 'Gamma', 'warning' => ' Advisory text. ']);
        $d = Fixtures::layer(['name' => 'Delta', 'warning' => " \t "]);

        Fixtures::record(['dataset_id' => $a, 'lon' => 150.0, 'lat' => -33.0, 'datestart' => '1820', 'dateend' => '1820']);
        Fixtures::record(['dataset_id' => $a, 'lon' => 151.25, 'lat' => -34.5, 'datestart' => '1840', 'dateend' => '1850']);
        Fixtures::record(['dataset_id' => $b, 'lon' => 152.0, 'lat' => -30.0]);
        Fixtures::record(['dataset_id' => $b, 'datestart' => 'junk']);
        Fixtures::record(['dataset_id' => $c, 'lon' => 149.5, 'lat' => -35.0, 'datestart' => '1860', 'dateend' => '1860']);
        Fixtures::record(['dataset_id' => $c, 'datestart' => '1850', 'dateend' => 'junk']);
        Fixtures::record(['dataset_id' => $d, 'lon' => 153.0, 'lat' => -31.0, 'datestart' => '  ', 'dateend' => '']);
        Fixtures::record(['lon' => 148.0, 'lat' => -36.0, 'datestart' => '1830']);
        Fixtures::record([]);
        Fixtures::record(['lon' => 154.5, 'lat' => -29.0, 'datestart' => '1855', 'dateend' => '']);

        return [$a, $b, $c, $d];
    }

    public function testWholeMatchSetFigures(): void
    {
        list($a, $b, $c, $d) = $this->seedFixture();

        $s = Summary::compute($this->builder(), true);

        $this->assertSame(['total', 'extent', 'no_coordinates_count', 'date_range', 'undated',
            'unparseable_dates', 'by_source', 'by_layer'], array_keys($s));
        $this->assertSame(10, $s['total']);
        $this->assertSame([148.0, -36.0, 154.5, -29.0], $s['extent']);
        $this->assertSame(3, $s['no_coordinates_count']);
        $this->assertSame(['start' => Civil::udate(1820), 'end' => Civil::udate(1861) - 1], $s['date_range']);
        $this->assertSame(['total' => 4, 'no_date' => 3, 'unparseable_date' => 1], $s['undated']);
        $this->assertSame(2, $s['unparseable_dates']);
        $this->assertSame(['layers' => 7, 'anps' => 3, 'ncg' => 0, 'text' => 0], $s['by_source']);
        $this->assertSame([
            ['layer_id' => $a, 'name' => 'Alpha', 'count' => 2, 'warning' => null],
            ['layer_id' => $b, 'name' => 'Beta', 'count' => 2, 'warning' => 'None'],
            ['layer_id' => $c, 'name' => 'Gamma', 'count' => 2, 'warning' => ' Advisory text. '],
            ['layer_id' => $d, 'name' => 'Delta', 'count' => 1, 'warning' => null],
        ], $s['by_layer']);
    }

    public function testByLayerOmittedWhenNotWanted(): void
    {
        $this->seedFixture();
        $s = Summary::compute($this->builder(), false);
        $this->assertArrayNotHasKey('by_layer', $s);
        $this->assertSame(10, $s['total']);
    }

    public function testFilteredSetAndCallerBuilderUntouched(): void
    {
        list($a) = $this->seedFixture();
        $builder = $this->builder(['layers' => (string) $a]);
        $before = $builder->toSql();

        $s = Summary::compute($builder, true);

        $this->assertSame($before, $builder->toSql());
        $this->assertSame(2, $s['total']);
        $this->assertSame([150.0, -34.5, 151.25, -33.0], $s['extent']);
        $this->assertSame(['layers' => 2, 'anps' => 0, 'ncg' => 0, 'text' => 0], $s['by_source']);
        $this->assertCount(1, $s['by_layer']);
    }

    public function testNoCoordinatesGivesNullExtent(): void
    {
        $l = Fixtures::layer();
        Fixtures::record(['dataset_id' => $l, 'datestart' => '1900']);

        $s = Summary::compute($this->builder(), false);

        $this->assertNull($s['extent']);
        $this->assertSame(1, $s['no_coordinates_count']);
    }

    public function testNoDatedRecordGivesNullDateRange(): void
    {
        $l = Fixtures::layer();
        Fixtures::record(['dataset_id' => $l, 'lon' => 150.0, 'lat' => -33.0]);

        $s = Summary::compute($this->builder(), false);

        $this->assertNull($s['date_range']);
        $this->assertSame(['total' => 1, 'no_date' => 1, 'unparseable_date' => 0], $s['undated']);
    }

    public function testEmptyMatchSet(): void
    {
        $empty = Fixtures::layer();
        $s = Summary::compute($this->builder(['layers' => (string) $empty]), true);

        $this->assertSame([
            'total' => 0,
            'extent' => null,
            'no_coordinates_count' => 0,
            'date_range' => null,
            'undated' => ['total' => 0, 'no_date' => 0, 'unparseable_date' => 0],
            'unparseable_dates' => 0,
            'by_source' => ['layers' => 0, 'anps' => 0, 'ncg' => 0, 'text' => 0],
            'by_layer' => [],
        ], $s);
    }
}
