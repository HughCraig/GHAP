<?php

namespace Tests\V2\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Sources;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\RecordQuery;

class RecordQueryTest extends TestCase
{
    private const ALLOWED = [
        'name', 'match', 'sources', 'layers', 'bbox', 'polygon', 'date_from', 'date_to',
        'include_undated', 'state', 'lga', 'feature_term', 'record_type', 'extended_data',
    ];

    private function filters(array $params): RecordFilters
    {
        $request = Request::create('/api/v2/test?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986));
        return RecordFilters::fromParams(ParameterSet::fromRequest($request, self::ALLOWED));
    }

    /** The sorted ids of the records the filters select. */
    private function ids(array $params): array
    {
        $f = $this->filters($params);
        RecordQuery::prepareFuzzy($f);
        $ids = array_map('intval', RecordQuery::build($f)->pluck('i.id')->all());
        sort($ids);
        return $ids;
    }

    private function assertIds(array $expected, array $params): void
    {
        sort($expected);
        $this->assertSame($expected, $this->ids($params), 'filters: ' . json_encode($params));
    }

    private function assertApiError(callable $fn, int $status, string $code, array $details): ApiException
    {
        try {
            $fn();
        } catch (ApiException $e) {
            $this->assertSame($status, $e->status());
            $this->assertSame($code, $e->errorCode());
            foreach ($details as $key => $value) {
                $this->assertSame($value, $e->details()[$key] ?? null, "details.$key");
            }
            return $e;
        }
        $this->fail("Expected ApiException $code");
    }

    private function extended(array $fields): string
    {
        $xml = '<ExtendedData>';
        foreach ($fields as [$name, $value]) {
            $xml .= '<Data name="' . $name . '"><value><![CDATA[' . $value . ']]></value></Data>';
        }
        return $xml . '</ExtendedData>';
    }

    // Visibility, sources, layers.

    public function testComputedOnceDateExpressionsAreTheirCounterpartsExactly(): void
    {
        foreach ([
            RecordQuery::LO => RecordQuery::R_LO,
            RecordQuery::HI => RecordQuery::R_HI,
            RecordQuery::NO_DATE => RecordQuery::R_NO_DATE,
            RecordQuery::UNPARSEABLE_SIDE => RecordQuery::R_UNPARSEABLE_SIDE,
        ] as $expression => $overColumns) {
            $this->assertSame($expression, strtr($overColumns, RecordQuery::R_COLUMNS));
        }
        $selected = [];
        foreach (RecordQuery::R_COLUMNS as $column => $expression) {
            $selected[] = $expression . ' as ' . substr($column, 2);
        }
        $this->assertSame(implode(', ', $selected), RecordQuery::ROW_DATE_COLUMNS);
    }

    public function testVisibilityExcludesPrivateLayersAndKeepsGazetteerRecords(): void
    {
        $public = Fixtures::layer();
        $private = Fixtures::layer(['public' => false]);
        $p = Fixtures::record(['dataset_id' => $public]);
        Fixtures::record(['dataset_id' => $private]);
        $anps = Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchausgaz')]);

        $this->assertIds([$p, $anps], []);
        $this->assertIds([$p], ['layers' => (string) $public]);
    }

    public function testSourcesSelectOnlyThoseDatasources(): void
    {
        $layer = Fixtures::layer();
        $l = Fixtures::record(['dataset_id' => $layer, 'datasource_id' => Fixtures::datasourceId('searchpublicdatasets')]);
        $a = Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchausgaz')]);
        $n = Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchncg')]);
        $t = Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchgeocoder')]);

        $this->assertIds([$a], ['sources' => 'anps']);
        $this->assertIds([$l], ['sources' => 'layers']);
        $this->assertIds([$t], ['sources' => 'text']);
        $this->assertIds([$a, $n], ['sources' => 'ncg,anps']);
        $this->assertIds([$l, $a, $n, $t], []);
    }

    public function testLayersSelectOnlyThoseLayers(): void
    {
        $a = Fixtures::layer();
        $b = Fixtures::layer();
        $c = Fixtures::layer();
        $ra1 = Fixtures::record(['dataset_id' => $a]);
        $ra2 = Fixtures::record(['dataset_id' => $a]);
        $rb = Fixtures::record(['dataset_id' => $b]);
        Fixtures::record(['dataset_id' => $c]);
        Fixtures::record();

        $this->assertIds([$ra1, $ra2], ['layers' => (string) $a]);
        $this->assertIds([$ra1, $ra2, $rb], ['layers' => "$b,$a"]);
    }

    public function testPrivateAndMissingLayersAreReported(): void
    {
        $private = Fixtures::layer(['public' => false]);
        $missing = 999999999;

        $this->assertApiError(function () use ($private) {
            $this->filters(['layers' => (string) $private]);
        }, 403, 'LAYER_PRIVATE', ['layer_id' => $private]);

        $this->assertApiError(function () use ($missing) {
            $this->filters(['layers' => (string) $missing]);
        }, 404, 'LAYER_NOT_FOUND', ['layer_id' => $missing]);

        $this->assertApiError(function () use ($missing, $private) {
            $this->filters(['layers' => "$missing,$private"]);
        }, 404, 'LAYER_NOT_FOUND', ['layer_id' => $missing]);

        $this->assertApiError(function () use ($missing, $private) {
            $this->filters(['layers' => "$private,$missing"]);
        }, 403, 'LAYER_PRIVATE', ['layer_id' => $private]);
    }

    // name and match.

    public function testExactNameMatchesTitleOrPlacenameCaseInsensitively(): void
    {
        $upper = Fixtures::record(['title' => 'WOLLOMBI']);
        $byPlacename = Fixtures::record(['title' => 'Post office', 'placename' => 'Wollombi']);
        Fixtures::record(['title' => 'Wollombi Brook']);
        Fixtures::record(['title' => 'Other', 'placename' => 'Wollombi Brook']);

        $this->assertIds([$upper, $byPlacename], ['name' => 'Wollombi', 'match' => 'exact']);
        $this->assertIds([$upper, $byPlacename], ['name' => 'Wollombi']);
    }

    public function testExactNameHasNoWildcards(): void
    {
        $percent = Fixtures::record(['title' => '50%']);
        Fixtures::record(['title' => '500']);
        Fixtures::record(['title' => '50']);
        $underscore = Fixtures::record(['title' => 'a_b']);
        Fixtures::record(['title' => 'axb']);
        $backslash = Fixtures::record(['title' => 'a\\b']);

        $this->assertIds([$percent], ['name' => '50%', 'match' => 'exact']);
        $this->assertIds([$underscore], ['name' => 'a_b', 'match' => 'exact']);
        $this->assertIds([$backslash], ['name' => 'a\\b', 'match' => 'exact']);
    }

    public function testContainsMatchesASubstring(): void
    {
        $brook = Fixtures::record(['title' => 'Wollombi Brook']);
        $byPlacename = Fixtures::record(['title' => 'Bridge', 'placename' => 'Upper Wollombi Brook']);
        Fixtures::record(['title' => 'Wollombi']);
        Fixtures::record(['title' => 'Brook']);

        $this->assertIds([$brook, $byPlacename], ['name' => 'bi br', 'match' => 'contains']);
    }

    public function testFuzzyUsesTheConfiguredThreshold(): void
    {
        $wollombi = Fixtures::record(['title' => 'Wollombi']);
        Fixtures::record(['title' => 'Sydney']);
        $byPlacename = Fixtures::record(['title' => 'Inn', 'placename' => 'Wollombi']);

        $this->assertIds([$wollombi, $byPlacename], ['name' => 'Wolombi', 'match' => 'fuzzy']);

        DB::select("select set_config('pg_trgm.similarity_threshold', '0.9', false)");
        $this->assertIds([$wollombi, $byPlacename], ['name' => 'Wolombi', 'match' => 'fuzzy']);
    }

    public function testMatchWithoutNameIsRejected(): void
    {
        $this->assertApiError(function () {
            $this->filters(['match' => 'contains']);
        }, 400, 'INVALID_PARAMETER', ['parameter' => 'match', 'rule' => 'requires']);
    }

    // bbox and polygon.

    public function testBboxIncludesTheBoundaryAndNeverMatchesMissingCoordinates(): void
    {
        $inside = Fixtures::record(['lon' => 150.5, 'lat' => -32.5]);
        $edge = Fixtures::record(['lon' => 151, 'lat' => -33]);
        Fixtures::record(['lon' => 151.5, 'lat' => -32.5]);
        Fixtures::record();

        $this->assertIds([$inside, $edge], ['bbox' => '150,-33,151,-32']);
    }

    public function testBboxAcrossTheAntimeridian(): void
    {
        $east = Fixtures::record(['lon' => 179.5, 'lat' => -15]);
        $west = Fixtures::record(['lon' => -179.5, 'lat' => -15]);
        Fixtures::record(['lon' => 0, 'lat' => -15]);
        Fixtures::record(['lon' => 179.5, 'lat' => -25]);

        $this->assertIds([$east, $west], ['bbox' => '179,-20,-179,-10']);
    }

    public function testPolygonMatchesInsidePointsOnly(): void
    {
        $inside = Fixtures::record(['lon' => 151, 'lat' => -32.5]);
        Fixtures::record(['lon' => 150.1, 'lat' => -31.2]);
        Fixtures::record(['lon' => 153, 'lat' => -32]);
        Fixtures::record();

        $this->assertIds([$inside], ['polygon' => '150 -33,152 -33,151 -31']);
    }

    public function testSelfIntersectingPolygonIsInvalidGeometry(): void
    {
        $e = $this->assertApiError(function () {
            $this->filters(['polygon' => '150 -33,152 -31,152 -33,150 -31']);
        }, 400, 'INVALID_GEOMETRY', ['parameter' => 'polygon']);
        $this->assertStringContainsString('Self-intersection', $e->details()['reason']);
    }

    // Dates.

    /** @return array<string,int> */
    private function datedFixtures(): array
    {
        return [
            '1860-06' => Fixtures::record(['datestart' => '1860-06']),
            '1820' => Fixtures::record(['datestart' => '1820', 'dateend' => '1820']),
            '1700-1830' => Fixtures::record(['datestart' => '1700', 'dateend' => '1830']),
            'no_date' => Fixtures::record(),
            'blank' => Fixtures::record(['datestart' => '  ', 'dateend' => '']),
            'unparseable' => Fixtures::record(['datestart' => 'junk', 'dateend' => 'circa 1850']),
            '1861' => Fixtures::record(['dateend' => '1861']),
            '1819-12-31' => Fixtures::record(['datestart' => '1819-12-31']),
        ];
    }

    public function testDateRangeKeepsOverlappingPeriodsAndUndatedRecords(): void
    {
        $r = $this->datedFixtures();
        $this->assertIds(
            [$r['1860-06'], $r['1820'], $r['1700-1830'], $r['no_date'], $r['blank'], $r['unparseable']],
            ['date_from' => '1820', 'date_to' => '1860']
        );
    }

    public function testIncludeUndatedFalseDropsUndatedAndUnparseableRecords(): void
    {
        $r = $this->datedFixtures();
        $this->assertIds(
            [$r['1860-06'], $r['1820'], $r['1700-1830']],
            ['date_from' => '1820', 'date_to' => '1860', 'include_undated' => 'false']
        );
    }

    public function testIncludeUndatedHasNoEffectWithoutADateFilter(): void
    {
        $r = $this->datedFixtures();
        $this->assertIds(array_values($r), ['include_undated' => 'false']);
    }

    public function testEitherDateBoundMayBeGivenAlone(): void
    {
        $r = $this->datedFixtures();
        $this->assertIds([$r['1860-06'], $r['1861']], ['date_from' => '1860-02', 'include_undated' => 'false']);
        $this->assertIds([$r['1700-1830'], $r['1819-12-31']], ['date_to' => '1819', 'include_undated' => 'false']);
    }

    public function testReversedRecordCoversItsWholeSpan(): void
    {
        $reversed = Fixtures::record(['datestart' => '1870', 'dateend' => '1850']);
        Fixtures::record(['datestart' => '1850', 'dateend' => '1854']);

        $this->assertIds([$reversed], ['date_from' => '1855', 'date_to' => '1856']);
    }

    public function testHalfUnparseableRecordIsDatedFromItsGoodSide(): void
    {
        $half = Fixtures::record(['datestart' => '1850', 'dateend' => 'junk']);

        $this->assertIds([$half], ['date_to' => '1850']);
        $this->assertIds([], ['date_from' => '1851']);
    }

    public function testDateFromAfterDateToIsRejected(): void
    {
        $this->assertApiError(function () {
            $this->filters(['date_from' => '1861', 'date_to' => '1860']);
        }, 400, 'INVALID_PARAMETER', ['parameter' => 'date_from', 'rule' => 'order']);
    }

    public function testDateFromWithinDateToPeriodIsAccepted(): void
    {
        $august = Fixtures::record(['datestart' => '1860-08']);
        Fixtures::record(['datestart' => '1860-03']);

        $f = $this->filters(['date_from' => '1860-06', 'date_to' => '1860']);
        $this->assertSame('1860-06', $f->toEcho()['date_from']);
        $this->assertSame('1860', $f->toEcho()['date_to']);
        $this->assertIds([$august], ['date_from' => '1860-06', 'date_to' => '1860', 'include_undated' => 'false']);
    }

    public function testWhitespaceOnlyDateSidesAreBlank(): void
    {
        $tab = Fixtures::record(['datestart' => "\t", 'dateend' => "\t"]);
        $cr = Fixtures::record(['datestart' => "\r"]);
        $mixed = Fixtures::record(['datestart' => "\n", 'dateend' => " \r\n\t"]);
        $halfCr = Fixtures::record(['datestart' => '1850', 'dateend' => "\r"]);
        $junk = Fixtures::record(['datestart' => "\tjunk\r"]);

        $where = function (string $condition): array {
            $ids = array_map('intval', DB::table('tlcmap.dataitem as i')->whereRaw($condition)->pluck('i.id')->all());
            sort($ids);
            return $ids;
        };
        $this->assertSame([$tab, $cr, $mixed], $where(RecordQuery::NO_DATE));
        $this->assertSame([$junk], $where(RecordQuery::UNPARSEABLE_SIDE));

        // The half-dated record is dated from its start; the blank records are undated.
        $this->assertIds([$halfCr], ['date_to' => '1850', 'include_undated' => 'false']);
        $this->assertIds([$tab, $cr, $mixed, $junk], ['date_from' => '1851']);
        $this->assertIds([], ['date_from' => '1851', 'include_undated' => 'false']);
    }

    public function testDateConstants(): void
    {
        $r = $this->datedFixtures();
        $half = Fixtures::record(['datestart' => '1850', 'dateend' => 'junk']);
        $reversed = Fixtures::record(['datestart' => '1870', 'dateend' => '1850']);

        $where = function (string $condition): array {
            $ids = array_map('intval', DB::table('tlcmap.dataitem as i')->whereRaw($condition)->pluck('i.id')->all());
            sort($ids);
            return $ids;
        };
        $this->assertSame([$r['no_date'], $r['blank']], $where(RecordQuery::NO_DATE));
        $this->assertSame([$r['unparseable'], $half], $where(RecordQuery::UNPARSEABLE_SIDE));

        $row = function (int $id): \stdClass {
            return DB::table('tlcmap.dataitem as i')->where('i.id', $id)->selectRaw(
                RecordQuery::LO . ' as lo, ' . RecordQuery::HI . ' as hi, '
                . RecordQuery::START_KEY . ' as sk, ' . RecordQuery::END_KEY . ' as ek'
            )->first();
        };
        $y = function (int $year): int {
            return \Tests\V2\Support\Civil::udate($year);
        };

        $rev = $row($reversed);
        $this->assertSame([$y(1850), $y(1871) - 1, $y(1870), $y(1850)],
            [(int) $rev->lo, (int) $rev->hi, (int) $rev->sk, (int) $rev->ek]);

        $h = $row($half);
        $this->assertSame([$y(1850), $y(1851) - 1, $y(1850), $y(1850)],
            [(int) $h->lo, (int) $h->hi, (int) $h->sk, (int) $h->ek]);

        $end = $row($r['1861']);
        $this->assertSame([$y(1861), $y(1862) - 1, $y(1861), $y(1861)],
            [(int) $end->lo, (int) $end->hi, (int) $end->sk, (int) $end->ek]);

        $none = $row($r['no_date']);
        $this->assertNull($none->lo);
        $this->assertNull($none->hi);
    }

    // Vocabulary filters.

    public function testLgaIsExactAndCaseInsensitive(): void
    {
        $cessnock = Fixtures::record(['lga' => 'CESSNOCK']);
        Fixtures::record(['lga' => 'Maitland']);
        Fixtures::record(['lga' => 'Cessnock Shire']);

        $this->assertIds([$cessnock], ['lga' => 'cessnock']);
        $this->assertIds([], ['lga' => 'Cessnock Council']);
    }

    public function testFeatureTermStateAndRecordTypeAreExactAndCaseInsensitive(): void
    {
        $siteTypeId = (int) DB::table('tlcmap.recordtype')->where('type', 'Site')->value('id');
        $town = Fixtures::record(['feature_term' => 'Town', 'state' => 'NSW', 'recordtype_id' => $siteTypeId]);
        $township = Fixtures::record(['feature_term' => 'township', 'state' => 'nsw coast']);
        Fixtures::record(['feature_term' => 'river', 'state' => 'VIC']);

        $this->assertIds([$town], ['feature_term' => 'town']);
        $this->assertIds([$township], ['feature_term' => 'TOWNSHIP']);
        $this->assertIds([$town], ['state' => 'nsw']);
        $this->assertIds([$town], ['record_type' => 'site']);
        $this->assertIds([], ['record_type' => 'sit']);
    }

    // extended_data.

    public function testExtendedDataNumericComparison(): void
    {
        $fiftyTwo = Fixtures::record(['extended_data' => $this->extended([['Years', '52']])]);
        $padded = Fixtures::record(['extended_data' => $this->extended([['Years', ' 60.5 ']])]);
        $fifty = Fixtures::record(['extended_data' => $this->extended([['Years', '50']])]);
        Fixtures::record(['extended_data' => $this->extended([['Years', 'abc']])]);
        Fixtures::record(['extended_data' => $this->extended([['Other', '99']])]);
        Fixtures::record(['extended_data' => '<ExtendedData><Data name="Years"><value>99']);
        Fixtures::record();

        $this->assertIds([$fiftyTwo, $padded], ['extended_data' => 'Years > 50']);
        $this->assertIds([], ['extended_data' => 'Years < 50']);
        $this->assertIds([$fifty], ['extended_data' => 'Years < 50.5']);
    }

    public function testExtendedDataBefore(): void
    {
        $y1849 = Fixtures::record(['extended_data' => $this->extended([['Built', '1849']])]);
        Fixtures::record(['extended_data' => $this->extended([['Built', '1850-06']])]);
        $dec1849 = Fixtures::record(['extended_data' => $this->extended([['Built', '1849-12']])]);
        Fixtures::record(['extended_data' => $this->extended([['Built', 'unknown']])]);
        Fixtures::record(['extended_data' => $this->extended([['Other', '1700']])]);

        $this->assertIds([$y1849, $dec1849], ['extended_data' => 'Built before 1850']);
    }

    public function testExtendedDataAfter(): void
    {
        Fixtures::record(['extended_data' => $this->extended([['Built', '1850-12-31']])]);
        $y1851 = Fixtures::record(['extended_data' => $this->extended([['Built', '1851']])]);
        Fixtures::record(['extended_data' => $this->extended([['Built', 'later']])]);

        $this->assertIds([$y1851], ['extended_data' => 'Built after 1850']);
    }

    public function testExtendedDataTextmatchIsCaseInsensitiveContains(): void
    {
        $bridge = Fixtures::record(['extended_data' => $this->extended([['Name', 'Old Bridge Inn']])]);
        Fixtures::record(['extended_data' => $this->extended([['Name', 'Ferry']])]);
        $percent = Fixtures::record(['extended_data' => $this->extended([['Name', '100% pure']])]);
        Fixtures::record(['extended_data' => $this->extended([['Name', '1000 pure']])]);

        $this->assertIds([$bridge], ['extended_data' => 'Name textmatch BRID']);
        $this->assertIds([$percent], ['extended_data' => 'Name textmatch 0%']);
    }

    public function testExtendedDataEqualsIsExactAndFirstDuplicateWins(): void
    {
        $dup = Fixtures::record(['extended_data' => $this->extended([['A', 'x'], ['A', 'y']])]);
        Fixtures::record(['extended_data' => $this->extended([['A', 'X']])]);
        Fixtures::record(['extended_data' => $this->extended([['A', 'x ']])]);

        $this->assertIds([$dup], ['extended_data' => 'A = x']);
        $this->assertIds([], ['extended_data' => 'A = y']);
    }

    public function testExtendedDataClausesAreCombinedWithAnd(): void
    {
        $both = Fixtures::record(['extended_data' => $this->extended([['Years', '60'], ['Name', 'Bridge']])]);
        Fixtures::record(['extended_data' => $this->extended([['Years', '60'], ['Name', 'Ferry']])]);
        Fixtures::record(['extended_data' => $this->extended([['Years', '10'], ['Name', 'Bridge']])]);

        $this->assertIds([$both], ['extended_data' => 'Years > 50 AND Name textmatch bridge']);
    }

    // Combination.

    public function testSeveralFiltersAreCombinedWithAnd(): void
    {
        $all = Fixtures::record([
            'title' => 'Wollombi Brook', 'lga' => 'Cessnock', 'lon' => 151.1, 'lat' => -32.9, 'datestart' => '1850',
        ]);
        Fixtures::record(['title' => 'Wollombi Brook', 'lga' => 'Maitland', 'lon' => 151.1, 'lat' => -32.9, 'datestart' => '1850']);
        Fixtures::record(['title' => 'Wollombi Brook', 'lga' => 'Cessnock', 'lon' => 140, 'lat' => -32.9, 'datestart' => '1850']);
        Fixtures::record(['title' => 'Paterson', 'lga' => 'Cessnock', 'lon' => 151.1, 'lat' => -32.9, 'datestart' => '1850']);
        Fixtures::record(['title' => 'Wollombi Brook', 'lga' => 'Cessnock', 'lon' => 151.1, 'lat' => -32.9, 'datestart' => '1900']);

        $this->assertIds([$all], [
            'name' => 'wollombi', 'match' => 'contains', 'lga' => 'cessnock',
            'bbox' => '150,-33,152,-32', 'date_to' => '1860', 'sources' => 'anps',
        ]);
    }

    // The echo and the record-level flag.

    public function testEchoWithDefaults(): void
    {
        $f = $this->filters([]);
        $this->assertSame([
            'name' => null,
            'match' => null,
            'sources' => Sources::ALL,
            'layers' => null,
            'bbox' => null,
            'polygon' => null,
            'date_from' => null,
            'date_to' => null,
            'include_undated' => true,
            'state' => null,
            'lga' => null,
            'feature_term' => null,
            'record_type' => null,
            'extended_data' => null,
        ], $f->toEcho());
        $this->assertFalse($f->hasRecordLevelFilter());
    }

    public function testEchoWithValues(): void
    {
        $layer = Fixtures::layer();
        $f = $this->filters([
            'name' => 'Wollombi', 'sources' => 'text,anps,anps', 'layers' => (string) $layer,
            'bbox' => '150,-33,151.5,-32', 'polygon' => '150 -33,152 -33,151 -31',
            'date_from' => '1820', 'date_to' => '1860-06', 'include_undated' => 'false',
            'state' => 'NSW', 'lga' => 'Cessnock', 'feature_term' => 'town', 'record_type' => 'Site',
            'extended_data' => 'Years > 50',
        ]);
        $this->assertSame([
            'name' => 'Wollombi',
            'match' => 'exact',
            'sources' => ['anps', 'text'],
            'layers' => [$layer],
            'bbox' => [150.0, -33.0, 151.5, -32.0],
            'polygon' => [[150.0, -33.0], [152.0, -33.0], [151.0, -31.0]],
            'date_from' => '1820',
            'date_to' => '1860-06',
            'include_undated' => false,
            'state' => 'NSW',
            'lga' => 'Cessnock',
            'feature_term' => 'town',
            'record_type' => 'Site',
            'extended_data' => 'Years > 50',
        ], $f->toEcho());
        $this->assertTrue($f->hasRecordLevelFilter());
        $this->assertSame('fuzzy', $this->filters(['name' => 'x', 'match' => 'fuzzy'])->toEcho()['match']);
    }

    public function testRecordLevelFilters(): void
    {
        $this->assertTrue($this->filters(['bbox' => '150,-33,151,-32'])->hasRecordLevelFilter());
        $this->assertTrue($this->filters(['polygon' => '150 -33,152 -33,151 -31'])->hasRecordLevelFilter());
        $this->assertTrue($this->filters(['date_from' => '1820'])->hasRecordLevelFilter());
        $this->assertTrue($this->filters(['date_to' => '1820'])->hasRecordLevelFilter());
        $this->assertFalse($this->filters(['include_undated' => 'false', 'name' => 'x'])->hasRecordLevelFilter());
    }
}
