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
        'include_undated', 'state', 'lga', 'parish', 'feature_term', 'record_type', 'extended_data',
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
        $this->assertSame(RecordQuery::LO, strtr(RecordQuery::X_LO, RecordQuery::X_COLUMNS));
        $this->assertSame(RecordQuery::HI, strtr(RecordQuery::X_HI, RecordQuery::X_COLUMNS));
    }

    /** Each column withDates computes from the ranges holds what R_COLUMNS says it holds. */
    public function testWithDatesColumnsEqualTheirExpressions(): void
    {
        $this->datedFixtures();
        foreach (['1850', '1870', '-5-02-29', '1856-03-04T14:30', '4/3/1856', 'junk', '', " \t", null] as $start) {
            foreach (['1850-06', '1849', '-0001', '00/00/1856', 'later', null] as $end) {
                Fixtures::record(['datestart' => $start, 'dateend' => $end]);
            }
        }

        $columns = array_map(function ($column) {
            return substr($column, 2);
        }, array_keys(RecordQuery::R_COLUMNS));
        $derived = RecordQuery::withDates(RecordQuery::build(RecordFilters::visible()), ['i.id'])
            ->select(array_merge(['r.id'], array_keys(RecordQuery::R_COLUMNS)))->orderBy('r.id')->get();
        $direct = RecordQuery::build(RecordFilters::visible())->select('i.id')
            ->selectRaw(implode(', ', array_map(function ($column, $expression) {
                return "$expression as $column";
            }, $columns, array_values(RecordQuery::R_COLUMNS))))->orderBy('i.id')->get();

        $this->assertGreaterThan(54, count($direct));
        $this->assertEquals($direct->all(), $derived->all());
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

    public function testExtendedDataNumbersTooLongForNumericDoNotMatch(): void
    {
        // numeric holds at most 131,072 digits before the point and 16,383 after; a cast beyond
        // either fails the whole query.
        $longest = Fixtures::record(['extended_data' => $this->extended([['n', str_repeat('7', 1000) . '.' . str_repeat('1', 1000)]])]);
        Fixtures::record(['extended_data' => $this->extended([['n', str_repeat('1', 140000)]])]);
        Fixtures::record(['extended_data' => $this->extended([['n', '0.' . str_repeat('1', 20000)]])]);
        Fixtures::record(['extended_data' => $this->extended([['n', '-' . str_repeat('1', 1001)]])]);
        Fixtures::record(['extended_data' => $this->extended([['n', '1.' . str_repeat('1', 1001)]])]);
        $six = Fixtures::record(['extended_data' => $this->extended([['n', '6']])]);

        $this->assertIds([$longest, $six], ['extended_data' => 'n > 5']);
        $this->assertIds([], ['extended_data' => 'n < 5']);
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

    // The extended_data pre-filter: raw text that reads differently once parsed still matches.

    private function rawRecord(string $data): int
    {
        return Fixtures::record(['extended_data' => '<ExtendedData>' . $data . '</ExtendedData>']);
    }

    public function testExtendedDataEntityReferencesStillMatch(): void
    {
        $amp = $this->rawRecord('<Data name="Fish &amp; Chips"><value>Salt &amp; Vinegar &lt;fine&gt;</value></Data>');
        $this->rawRecord('<Data name="Fish &amp; Chips"><value>Salt</value></Data>');

        $this->assertIds([$amp], ['extended_data' => '"Fish & Chips" = "Salt & Vinegar <fine>"']);
        $this->assertIds([$amp], ['extended_data' => '"Fish & Chips" textmatch "VINEGAR <F"']);
    }

    public function testExtendedDataNumericCharacterReferencesStillMatch(): void
    {
        $name = $this->rawRecord('<Data name="R&#101;gion"><value>NSW</value></Data>');
        $value = $this->rawRecord('<Data name="Region"><value>&#78;S&#x57;</value></Data>');
        $cr = $this->rawRecord('<Data name="Note"><value>line&#x0d;break</value></Data>');
        $number = $this->rawRecord('<Data name="C&#111;unt"><value>&#52;2</value></Data>');
        $this->rawRecord('<Data name="Region"><value>VIC</value></Data>');

        $this->assertIds([$name, $value], ['extended_data' => 'Region = NSW']);
        $this->assertIds([$name, $value], ['extended_data' => 'Region textmatch nsw']);
        $this->assertIds([$cr], ['extended_data' => 'Note textmatch "LINE"']);
        $this->assertIds([$number], ['extended_data' => 'Count > 41']);
        $this->assertIds([$number], ['extended_data' => 'Count < 43']);
    }

    public function testExtendedDataWhitespaceInTheNameAttributeStillMatches(): void
    {
        $tab = $this->rawRecord("<Data name=\"Place\tType\"><value>Inn</value></Data>");
        $crlf = $this->rawRecord("<Data name=\"Place\r\nType\"><value>Inn</value></Data>");
        $this->rawRecord('<Data name="PlaceType"><value>Inn</value></Data>');

        $this->assertIds([$tab, $crlf], ['extended_data' => '"Place Type" = Inn']);
        $this->assertIds([$tab, $crlf], ['extended_data' => '"Place Type" textmatch INN']);
    }

    public function testExtendedDataSingleQuotedAttributesStillMatch(): void
    {
        $single = $this->rawRecord("<Data name='Kind'><value>Hotel</value></Data>");
        $quotes = $this->rawRecord("<Data name='Say \"hi\"'><value>It's here</value></Data>");

        $this->assertIds([$single], ['extended_data' => 'Kind = Hotel']);
        $this->assertIds([$quotes], ['extended_data' => '\'Say "hi"\' = "It\'s here"']);
    }

    public function testExtendedDataCdataAndMarkupInsideAValueStillMatch(): void
    {
        $cdata = $this->rawRecord('<Data name="Note"><value><![CDATA[a <b> & "c"]]></value></Data>');
        $split = $this->rawRecord('<Data name="State"><value><![CDATA[N]]><![CDATA[SW]]></value></Data>');
        $mixed = $this->rawRecord('<Data name="State"><value>N<![CDATA[SW]]></value></Data>');
        $nested = $this->rawRecord('<Data name="State"><value>N<b>S</b>W</value></Data>');
        $comment = $this->rawRecord('<Data name="State"><value>N<!-- a comment -->SW</value></Data>');
        $this->rawRecord('<Data name="State"><value><![CDATA[NS]]></value></Data>');

        $this->assertIds([$cdata], ['extended_data' => 'Note = \'a <b> & "c"\'']);
        $this->assertIds([$split, $mixed, $nested, $comment], ['extended_data' => 'State = NSW']);
        $this->assertIds([$split, $mixed, $nested, $comment], ['extended_data' => 'State textmatch nsw']);
    }

    public function testExtendedDataTextmatchIgnoresCaseIncludingNonAscii(): void
    {
        $bridge = Fixtures::record(['extended_data' => $this->extended([['Name', 'Old BRIDGE Inn']])]);
        $accented = Fixtures::record(['extended_data' => $this->extended([['Name', 'ÖLBERG Café']])]);
        Fixtures::record(['extended_data' => $this->extended([['Name', 'Ferry']])]);

        $this->assertIds([$bridge], ['extended_data' => 'Name textmatch "bridge inn"']);
        $this->assertIds([$accented], ['extended_data' => 'Name textmatch "ölberg CAFÉ"']);
        $this->assertIds([], ['extended_data' => 'Name = "old bridge inn"']);
    }

    public function testExtendedDataSqlCarriesTheRawTextPrefilter(): void
    {
        $f = $this->filters(['extended_data' => 'State = "N_S W" AND Name textmatch "Old 100%" AND Years > 5']);
        $q = RecordQuery::build($f);
        $sql = $q->toSql();

        $this->assertSame(1, substr_count($sql, 'i.extended_data ILIKE CAST(? AS text) ' . RecordQuery::ESCAPE));
        $this->assertSame(4, substr_count($sql, 'i.extended_data LIKE CAST(? AS text) ' . RecordQuery::ESCAPE));
        // The first pre-filter comes before the first parse.
        $this->assertLessThan(strpos($sql, 'tlcmap.v2_extended_value'), strpos($sql, 'i.extended_data LIKE'));
        foreach (['%State%', '%N\\_S%', '%Name%', '%100\\%%', '%Years%'] as $pattern) {
            $this->assertContains($pattern, $q->getBindings());
        }
    }

    /**
     * The pre-filters can be answered from the indexes alone, the fallback for records not written
     * as they read included, so no extended_data clause needs to read every record. Sequential and
     * plain index scans are switched off, since on a near-empty table the planner prefers them.
     */
    public function testExtendedDataPrefiltersAreServedByTheIndexes(): void
    {
        DB::statement('SET LOCAL enable_seqscan = off');
        DB::statement('SET LOCAL enable_indexscan = off');
        foreach (['State = NSW', 'Name textmatch "Old Inn"', 'Years > 5', 'Built before 1850'] as $expression) {
            $q = DB::table('tlcmap.dataitem as i')->select('i.id');
            foreach ($this->filters(['extended_data' => $expression])->extendedData->clauses() as $clause) {
                foreach (RecordQuery::prefilter($clause) as list($sql, $bindings)) {
                    $q->whereRaw($sql, $bindings);
                }
            }
            $plan = implode("\n", array_map(function ($row) {
                return $row->{'QUERY PLAN'};
            }, DB::select('EXPLAIN ' . $q->toSql(), $q->getBindings())));

            $this->assertStringContainsString('v2_dataitem_extended_data_trgm', $plan, $expression);
            $this->assertStringContainsString('v2_dataitem_extended_data_unliteral', $plan, $expression);
            $this->assertStringNotContainsString('Seq Scan on dataitem', $plan, $expression);
        }
    }

    public function testLiteralRunIsTheLongestRunWithoutWhitespaceOrMarkupCharacters(): void
    {
        $this->assertSame('Chips', RecordQuery::literalRun('Fish & Chips'));
        $this->assertSame('Fish', RecordQuery::literalRun('Fish &amp'));
        $this->assertSame('quoted', RecordQuery::literalRun('a "quoted" one'));
        $this->assertSame('Types', RecordQuery::literalRun("Pl\tTypes"));
        $this->assertSame('Place', RecordQuery::literalRun("Place\nType"));
        $this->assertSame('Ölberg', RecordQuery::literalRun('<Ölberg>'));
        $this->assertSame('100%', RecordQuery::literalRun('100%'));
        $this->assertSame('', RecordQuery::literalRun("& <> ' \""));
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
            'parish' => null,
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
            'state' => 'NSW', 'lga' => 'Cessnock', 'parish' => 'Wollombi', 'feature_term' => 'town', 'record_type' => 'Site',
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
            'parish' => 'Wollombi',
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
