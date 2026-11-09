<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/**
 * The vocabulary and place search agree (spec §5.6), over HTTP only: for every filter set and
 * every field, each entry's record_count is the total place search reports for that value, the
 * counts add up to the filtered record set, and each entry's spellings add up to its count.
 */
class VocabularySearchInvariantTest extends TestCase
{
    private const FIELDS = ['feature_term', 'state', 'lga', 'parish', 'record_type'];

    private const IN = ['lat' => -32.7, 'lon' => 151.2];
    private const BOX_ONLY = ['lat' => -32.5, 'lon' => 150.9];
    private const OUT = ['lat' => -35.0, 'lon' => 149.0];

    /** Stored text values: case variants, padding, blanks and values that need encoding. */
    private const TEXT = [
        'Hill', 'HILL', ' hill ', "hill\t", 'Creek', 'creek ', "Break O'Day", 'Smith, John',
        'A&B', 'C+D', '50%', 'ÉCOLE', 'école', 'NSW', 'nsw', ' NSW ',
        '', '   ', "\t", "\r\n", null,
    ];

    /** Record type names to add beside the seeded ones. */
    private const TYPES = [
        'Hill', 'HILL', ' hill ', "Break O'Day", 'Smith, John', 'A&B', 'C+D', '50%', 'ÉCOLE', 'école',
        ' Site ', '', '   ',
    ];

    private const DATES = ['1850', '1830', '1900', 'circa 1850', null, '1820', '1860', '1700'];
    private const TITLES = ['Wollombi', 'Wollombi North', 'Wolombi', 'Cessnock', 'WOLLOMBI'];
    private const YEARS = ['52', '40', 'abc', null, ' 60.5 '];

    private $layers = [];

    private function extended(string $years): string
    {
        return '<ExtendedData><Data name="Years"><value><![CDATA[' . $years . ']]></value></Data></ExtendedData>';
    }

    private function fixture(): void
    {
        $a = Fixtures::layer(['name' => 'Layer A']);
        $b = Fixtures::layer(['name' => 'Layer B']);
        $c = Fixtures::layer(['name' => 'Layer C']);
        $private = Fixtures::layer(['name' => 'Private', 'public' => false]);
        $this->layers = ['A' => $a, 'B' => $b];

        $typeIds = DB::table('tlcmap.recordtype')->pluck('id')->all();
        foreach (self::TYPES as $type) {
            $typeIds[] = DB::table('tlcmap.recordtype')->insertGetId(['type' => $type]);
        }
        $typeIds[] = null;

        $places = [self::IN, self::BOX_ONLY, self::OUT, []];
        $homes = [$a, $b, $c, $private, null, null, null];
        $sources = [Fixtures::datasourceId('searchanps'), Fixtures::datasourceId('searchncg')];

        $text = count(self::TEXT);
        for ($i = 0; $i < 180; $i++) {
            $home = $homes[$i % count($homes)];
            $row = $places[$i % count($places)] + [
                'title' => self::TITLES[$i % count(self::TITLES)],
                'datestart' => self::DATES[$i % count(self::DATES)],
                'feature_term' => self::TEXT[$i % $text],
                'state' => self::TEXT[($i * 5 + 1) % $text],
                'lga' => self::TEXT[($i * 7 + 2) % $text],
                'parish' => self::TEXT[($i * 11 + 3) % $text],
                'recordtype_id' => $typeIds[($i * 3) % count($typeIds)],
            ];
            if ($i % 6 === 0) {
                $row['dateend'] = self::DATES[($i + 2) % count(self::DATES)];
            }
            $years = self::YEARS[$i % count(self::YEARS)];
            if ($years !== null) {
                $row['extended_data'] = $this->extended($years);
            }
            $row['datasource_id'] = $home !== null ? 1 : $sources[$i % 2];
            if ($home !== null) {
                $row['dataset_id'] = $home;
            }
            Fixtures::record($row);
        }
    }

    public function filterSets(): array
    {
        return [
            'no filters' => [[]],
            'bbox' => [['bbox' => '150,-34,152,-32']],
            'polygon' => [['polygon' => '150 -34,152 -34,151 -32']],
            'dates' => [['date_from' => '1820', 'date_to' => '1860']],
            'dates without undated' => [['date_from' => '1820', 'date_to' => '1860', 'include_undated' => 'false']],
            'sources' => [['sources' => 'anps,ncg']],
            'layers' => [['layers' => '{A},{B}']],
            'state' => [['state' => 'nsw']],
            'name exact' => [['name' => 'Wollombi']],
            'name contains' => [['name' => 'Woll', 'match' => 'contains']],
            'name fuzzy' => [['name' => 'Wolombi', 'match' => 'fuzzy']],
            'extended data' => [['extended_data' => 'Years > 50']],
        ];
    }

    private function url(string $path, array $query): string
    {
        return $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    private function total(array $filters): int
    {
        $r = $this->get($this->url('/api/v2/places', $filters + ['summary_only' => 'true']));
        $r->assertStatus(200);

        return $r->json('summary.total');
    }

    /** Every entry of one field's vocabulary, paged to the end at three per page. */
    private function vocabulary(array $filters, string $field): array
    {
        $url = $this->url('/api/v2/vocabulary', $filters + ['field' => $field, 'per_page' => 3]);
        $entries = [];
        $records = null;
        $pages = 0;
        while ($url !== null) {
            $r = $this->get($url);
            $r->assertStatus(200);
            $records = $r->json('records');
            foreach ($r->json('values') as $entry) {
                $entries[] = $entry;
            }
            $this->assertLessThan(200, ++$pages, 'Paging did not end for ' . $field);
            $url = $r->json('page.next');
        }

        return [$entries, $records];
    }

    /** @dataProvider filterSets */
    public function test_every_vocabulary_count_is_the_place_search_total_for_its_value(array $filters)
    {
        $this->fixture();
        foreach ($filters as $key => $value) {
            $filters[$key] = strtr($value, ['{A}' => $this->layers['A'], '{B}' => $this->layers['B']]);
        }
        $set = json_encode($filters, JSON_UNESCAPED_UNICODE);

        $matched = $this->total($filters);
        $this->assertGreaterThan(0, $matched, "Filter set $set matches nothing.");
        if ($filters !== []) {
            // Every filter set separates the fixture: it drops some records.
            $this->assertLessThan($this->total([]), $matched, "Filter set $set drops nothing.");
        }

        $multiple = $blanks = false;
        foreach (self::FIELDS as $field) {
            list($entries, $records) = $this->vocabulary($filters, $field);
            $where = "filter set $set, field $field";
            $this->assertNotEmpty($entries, "$where: the vocabulary has no entries");
            foreach ($entries as $e) {
                $multiple = $multiple || count($e['spellings']) > 1;
            }
            $blanks = $blanks || $records['without_value'] > 0;
            $this->assertGreaterThan(0, $records['with_value'], "$where: no record has a value");

            $this->assertSame($matched, $records['total'], "$where: records.total differs from place search");
            $this->assertSame(
                $records['total'],
                $records['with_value'] + $records['without_value'],
                "$where: with_value + without_value is not records.total"
            );

            $sum = 0;
            foreach ($entries as $entry) {
                $value = $entry['value'];
                $label = "$where, value " . json_encode($value, JSON_UNESCAPED_UNICODE);
                $this->assertNotSame('', $value, "$where: an entry has an empty value");

                $searched = $this->total([$field => $value] + $filters);
                $this->assertSame(
                    $searched,
                    $entry['record_count'],
                    "$label: record_count {$entry['record_count']} differs from place search $searched"
                );

                $spellings = array_sum(array_column($entry['spellings'], 'record_count'));
                $this->assertSame(
                    $entry['record_count'],
                    $spellings,
                    "$label: spellings add to $spellings, not {$entry['record_count']}"
                );
                $sum += $entry['record_count'];
            }
            $this->assertSame($records['with_value'], $sum, "$where: entry counts differ from with_value");
        }
        if ($filters === []) {
            // The fixture guarantees both, so the checks above are not vacuous.
            $this->assertTrue($multiple, "No entry in the unfiltered set has more than one spelling");
            $this->assertTrue($blanks, "No record in the unfiltered set lacks a value");
        }
    }
}
