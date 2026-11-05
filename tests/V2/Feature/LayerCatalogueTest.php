<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Civil;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/** GET /api/v2/layers — the catalogue (spec §10.2, DESIGN ①). */
class LayerCatalogueTest extends TestCase
{
    /** Every parameter of spec §10.2, in echo order. */
    private const ECHO_KEYS = [
        'q', 'bbox', 'polygon', 'date_from', 'date_to', 'include_undated', 'record_type', 'creator',
        'has_license', 'has_warning', 'min_records', 'sort', 'direction', 'cursor', 'per_page',
    ];

    private const ENTRY_KEYS = [
        'id', 'name', 'description', 'creator', 'record_type', 'record_count', 'matching_count', 'bbox',
        'date_range', 'undated', 'has_license', 'has_warning', 'url',
    ];

    /** A box around the Hunter Valley. */
    private const HUNTER = '150.8,-33,151.6,-32.4';

    private function catalogue(string $qs = '')
    {
        $r = $this->get('/api/v2/layers' . ($qs === '' ? '' : '?' . $qs));
        $r->assertStatus(200);
        return $r;
    }

    private function ids($r): array
    {
        return array_map(function (array $layer) {
            return $layer['id'];
        }, $r->json('layers'));
    }

    private function entry($r, int $id): array
    {
        foreach ($r->json('layers') as $layer) {
            if ($layer['id'] === $id) {
                return $layer;
            }
        }
        $this->fail("Layer $id is not in the catalogue.");
    }

    public function test_1_bbox_matches_records_never_extents()
    {
        $a = Fixtures::layer(['name' => 'A']);
        Fixtures::record(['dataset_id' => $a, 'lat' => -32.7, 'lon' => 151.2]);
        Fixtures::record(['dataset_id' => $a, 'lat' => 51.5, 'lon' => -0.1]);
        Fixtures::record(['dataset_id' => $a, 'lat' => 40.7, 'lon' => -74.0]);
        Fixtures::record(['dataset_id' => $a, 'lat' => -45.0, 'lon' => 170.0]);
        // B's extent covers the box, and so does its declared extent, but no record lies inside it.
        $b = Fixtures::layer([
            'name' => 'B',
            'latitude_from' => -34, 'latitude_to' => -32, 'longitude_from' => 150, 'longitude_to' => 152,
        ]);
        Fixtures::record(['dataset_id' => $b, 'lat' => -34.0, 'lon' => 150.0]);
        Fixtures::record(['dataset_id' => $b, 'lat' => -32.0, 'lon' => 152.0]);

        $r = $this->catalogue('bbox=' . self::HUNTER);
        $this->assertSame([$a], $this->ids($r));
        $this->assertSame(1, $r->json('total'));
        $this->assertSame(1, $r->json('layers.0.matching_count'));
        $this->assertSame(4, $r->json('layers.0.record_count'));
    }

    public function test_1_response_and_entry_shape()
    {
        $id = Fixtures::layer(['name' => 'Shape', 'creator' => 'Someone']);
        Fixtures::record(['dataset_id' => $id]);

        $r = $this->catalogue();
        $this->assertSame(['query', 'total', 'page', 'layers'], array_keys($r->json()));
        $this->assertSame(self::ECHO_KEYS, array_keys($r->json('query')));
        $this->assertSame(['returned', 'per_page', 'next_cursor', 'next'], array_keys($r->json('page')));
        $this->assertSame(self::ENTRY_KEYS, array_keys($r->json('layers.0')));
        $this->assertSame(100, $r->json('page.per_page'));
        $this->assertSame('id', $r->json('query.sort'));
        $this->assertSame('asc', $r->json('query.direction'));
        $this->assertTrue($r->json('query.include_undated'));
        $this->assertNull($r->json('query.q'));
        $this->assertNull($r->json('query.has_license'));
        $this->assertSame('http://localhost/layers/' . $id, $r->json('layers.0.url'));
        $this->assertSame('Shape', $r->json('layers.0.name'));
        $this->assertNull($r->json('layers.0.description'));
        $this->assertSame('Someone', $r->json('layers.0.creator'));
        $this->assertStringContainsString('application/json; charset=utf-8', $r->headers->get('Content-Type'));
    }

    public function test_1_echo_carries_every_given_value()
    {
        $r = $this->catalogue(
            'q=x&bbox=150,-34,152,-32&polygon=150 -33,152 -33,151 -31&date_from=1800&date_to=1900'
            . '&include_undated=false&record_type=Other&creator=c&has_license=true&has_warning=false'
            . '&min_records=2&sort=name&direction=desc&per_page=7'
        );
        $this->assertSame([
            'q' => 'x',
            'bbox' => [150, -34, 152, -32],
            'polygon' => [[150, -33], [152, -33], [151, -31]],
            'date_from' => '1800',
            'date_to' => '1900',
            'include_undated' => false,
            'record_type' => 'Other',
            'creator' => 'c',
            'has_license' => true,
            'has_warning' => false,
            'min_records' => 2,
            'sort' => 'name',
            'direction' => 'desc',
            'cursor' => null,
            'per_page' => 7,
        ], $r->json('query'));
    }

    public function test_1_rejected_parameters()
    {
        foreach (['name=x', 'sources=anps', 'layers=1', 'nulls=first', 'limit=3', 'state=NSW', 'sample=2'] as $qs) {
            $r = $this->get('/api/v2/layers?' . $qs);
            $r->assertStatus(400);
            $this->assertSame('unknown', $r->json('error.details.rule'), $qs);
        }
        foreach ([
            'sort=title' => 'sort', 'per_page=1001' => 'per_page', 'has_license=yes' => 'has_license',
            'min_records=-1' => 'min_records', 'min_records=x' => 'min_records', 'cursor=abc' => 'cursor',
        ] as $qs => $parameter) {
            $r = $this->get('/api/v2/layers?' . $qs);
            $r->assertStatus(400);
            $this->assertSame($parameter, $r->json('error.details.parameter'), $qs);
        }
        // A cursor belongs to its query.
        Fixtures::layer();
        Fixtures::layer();
        $cursor = $this->catalogue('per_page=1')->json('page.next_cursor');
        $this->assertNotNull($cursor);
        $this->get('/api/v2/layers?per_page=1&q=x&cursor=' . $cursor)->assertStatus(400);
        $this->catalogue('per_page=1&cursor=' . $cursor);
    }

    public function test_1_computed_figures_ignore_declared_fields()
    {
        $id = Fixtures::layer([
            'latitude_from' => 10, 'latitude_to' => 20, 'longitude_from' => 30, 'longitude_to' => 40,
            'temporal_from' => '1700', 'temporal_to' => '1710',
        ]);
        Fixtures::record(['dataset_id' => $id, 'lat' => -33.0, 'lon' => 151.0, 'datestart' => '1850']);
        Fixtures::record(['dataset_id' => $id, 'lat' => -32.0, 'lon' => 152.0, 'datestart' => '1900', 'dateend' => '1910']);
        Fixtures::record(['dataset_id' => $id]);
        Fixtures::record(['dataset_id' => $id, 'datestart' => 'circa']);
        // Records of other layers and the gazetteers do not count.
        Fixtures::record(['dataset_id' => Fixtures::layer(), 'lat' => 0.0, 'lon' => 0.0, 'datestart' => '1600']);
        Fixtures::record(['lat' => 0.0, 'lon' => 0.0]);

        $layer = $this->entry($this->catalogue(), $id);
        $this->assertSame(4, $layer['record_count']);
        $this->assertSame([151, -33, 152, -32], $layer['bbox']);
        $this->assertSame(['start' => Civil::udate(1850), 'end' => Civil::udate(1911) - 1], $layer['date_range']);
        $this->assertSame(['total' => 2, 'no_date' => 1, 'unparseable_date' => 1], $layer['undated']);
        $this->assertNull($layer['matching_count']);

        // The same figures as layer detail's summary of the layer's records.
        $summary = $this->get('/api/v2/layers/' . $id)->json('summary');
        $this->assertSame($summary['total'], $layer['record_count']);
        $this->assertSame($summary['extent'], $layer['bbox']);
        $this->assertSame($summary['date_range'], $layer['date_range']);
        $this->assertSame($summary['undated'], $layer['undated']);
    }

    public function test_1_private_layers_never_and_empty_layers_listed()
    {
        $private = Fixtures::layer(['public' => false]);
        Fixtures::record(['dataset_id' => $private, 'lat' => -32.7, 'lon' => 151.2, 'datestart' => '1850']);
        $empty = Fixtures::layer();
        $full = Fixtures::layer();
        Fixtures::record(['dataset_id' => $full, 'lat' => -32.7, 'lon' => 151.2, 'datestart' => '1850']);
        Fixtures::record(['dataset_id' => $full]);

        $r = $this->catalogue();
        $this->assertSame([$empty, $full], $this->ids($r));
        $layer = $this->entry($r, $empty);
        $this->assertSame(0, $layer['record_count']);
        $this->assertNull($layer['bbox']);
        $this->assertNull($layer['date_range']);
        $this->assertSame(['total' => 0, 'no_date' => 0, 'unparseable_date' => 0], $layer['undated']);

        $this->assertSame([$full], $this->ids($this->catalogue('bbox=' . self::HUNTER)));
        $this->assertSame([$full], $this->ids($this->catalogue('date_from=1800')));
        $this->assertSame([$full], $this->ids($this->catalogue('min_records=1')));
        $this->assertSame([$empty, $full], $this->ids($this->catalogue('include_undated=false')));
    }

    public function test_1_q_creator_and_record_type()
    {
        $byName = Fixtures::layer(['name' => 'Wollombi Convict Roads']);
        $byDescription = Fixtures::layer(['description' => 'Notes on the CONVICT era']);
        $byKeyword = Fixtures::layer(['name' => 'K']);
        Fixtures::keyword($byKeyword, 'Convicts');
        Fixtures::keyword(Fixtures::layer(['name' => 'Other']), 'music');
        $percent = Fixtures::layer(['name' => '100% sure']);

        $this->assertSame([$byName, $byDescription, $byKeyword], $this->ids($this->catalogue('q=convict')));
        $this->assertSame([$percent], $this->ids($this->catalogue('q=' . rawurlencode('0%'))));
        $none = $this->catalogue('q=' . rawurlencode('_onvict%'));
        $this->assertSame([], $this->ids($none));
        $this->assertSame(0, $none->json('total'));

        $alice = Fixtures::layer(['creator' => 'Dr Alice Smith']);
        Fixtures::layer(['creator' => 'Bob']);
        $this->assertSame([$alice], $this->ids($this->catalogue('creator=alice')));

        $types = DB::table('tlcmap.recordtype')->pluck('id', 'type')->all();
        $event = Fixtures::layer(['recordtype_id' => $types['Event']]);
        Fixtures::layer(['recordtype_id' => $types['Person']]);
        $r = $this->catalogue('record_type=eVeNt');
        $this->assertSame([$event], $this->ids($r));
        $this->assertSame('Event', $r->json('layers.0.record_type'));
        $this->assertSame([], $this->ids($this->catalogue('record_type=Even')));
    }

    public function test_1_record_type_is_the_layers_not_its_records()
    {
        $types = DB::table('tlcmap.recordtype')->pluck('id', 'type')->all();
        // An Event layer whose one record is typed Person, and the reverse.
        $event = Fixtures::layer(['recordtype_id' => $types['Event']]);
        Fixtures::record(['dataset_id' => $event, 'recordtype_id' => $types['Person'], 'lat' => -32.7, 'lon' => 151.2]);
        $person = Fixtures::layer(['recordtype_id' => $types['Person']]);
        Fixtures::record(['dataset_id' => $person, 'recordtype_id' => $types['Event'], 'lat' => -32.7, 'lon' => 151.2]);

        $this->assertSame([$event], $this->ids($this->catalogue('record_type=Event')));
        $this->assertSame([$event], $this->ids($this->catalogue('record_type=Event&bbox=' . self::HUNTER)));
    }

    public function test_1_licence_and_warning_presence()
    {
        $none = Fixtures::layer(['license' => 'None', 'warning' => 'None']);
        $blank = Fixtures::layer(['license' => " \t\r\n", 'warning' => '   ']);
        $null = Fixtures::layer();
        $text = Fixtures::layer(['license' => 'CC BY 4.0', 'warning' => 'Aboriginal and Torres Strait Islander']);

        $r = $this->catalogue('has_license=true');
        $this->assertSame([$none, $text], $this->ids($r));
        $this->assertTrue($r->json('layers.0.has_license'));
        $this->assertSame([$blank, $null], $this->ids($this->catalogue('has_license=false')));
        $r = $this->catalogue('has_warning=false');
        $this->assertSame([$blank, $null], $this->ids($r));
        $this->assertFalse($r->json('layers.0.has_warning'));
        $this->assertSame([$none, $text], $this->ids($this->catalogue('has_warning=true')));
    }

    public function test_1_min_records()
    {
        $one = Fixtures::layer();
        Fixtures::record(['dataset_id' => $one]);
        $two = Fixtures::layer();
        Fixtures::record(['dataset_id' => $two]);
        Fixtures::record(['dataset_id' => $two]);
        $three = Fixtures::layer();
        for ($n = 0; $n < 3; $n++) {
            Fixtures::record(['dataset_id' => $three]);
        }
        Fixtures::layer();

        $r = $this->catalogue('min_records=2');
        $this->assertSame([$two, $three], $this->ids($r));
        $this->assertSame(2, $r->json('total'));
        // Whole layer: one record in the box still passes min_records=2.
        Fixtures::record(['dataset_id' => $two, 'lat' => -32.7, 'lon' => 151.2]);
        $r = $this->catalogue('min_records=2&bbox=' . self::HUNTER);
        $this->assertSame([$two], $this->ids($r));
        $this->assertSame(1, $r->json('layers.0.matching_count'));
        $this->assertSame(3, $r->json('layers.0.record_count'));
    }

    public function test_1_matching_count_only_with_a_record_level_filter()
    {
        $id = Fixtures::layer();
        Fixtures::record(['dataset_id' => $id, 'datestart' => '1850']);
        Fixtures::record(['dataset_id' => $id, 'datestart' => '1950']);
        Fixtures::record(['dataset_id' => $id]);

        $this->assertNull($this->catalogue()->json('layers.0.matching_count'));
        $this->assertNull($this->catalogue('include_undated=false')->json('layers.0.matching_count'));
        $this->assertNull($this->catalogue('q=Layer')->json('layers.0.matching_count'));
        $this->assertSame(2, $this->catalogue('date_to=1900')->json('layers.0.matching_count'));
        $this->assertSame(1, $this->catalogue('date_to=1900&include_undated=false')->json('layers.0.matching_count'));
        $this->assertSame(3, $this->catalogue('date_to=1900')->json('layers.0.record_count'));
    }

    public function test_3_paging_by_name_visits_each_layer_once()
    {
        // dataset.name is NOT NULL in the schema; the constraint is lifted inside this test's
        // transaction so null names exercise the nullable sort key at a page boundary.
        DB::statement('ALTER TABLE tlcmap.dataset ALTER COLUMN name DROP NOT NULL');
        $ids = [];
        foreach (['b', 'B', 'a', null, null] as $name) {
            $ids[] = Fixtures::layer(['name' => $name]);
        }

        foreach (['asc', 'desc'] as $direction) {
            $seen = [];
            $pages = 0;
            $r = $this->catalogue('per_page=2&sort=name&direction=' . $direction);
            while (true) {
                $pages++;
                $this->assertSame(5, $r->json('total'));
                $this->assertLessThanOrEqual(2, $r->json('page.returned'));
                $seen = array_merge($seen, $this->ids($r));
                if ($r->json('page.next') === null) {
                    break;
                }
                $this->assertLessThan(10, $pages, 'Paging did not end.');
                $r = $this->get($r->json('page.next'));
                $r->assertStatus(200);
            }
            $this->assertSame(3, $pages, $direction);
            $sorted = $seen;
            sort($sorted);
            $expected = $ids;
            sort($expected);
            $this->assertSame($expected, $sorted, $direction);
            $this->assertSame($this->ids($this->catalogue('sort=name&direction=' . $direction)), $seen, $direction);
            // Null names come last in either direction.
            $this->assertEqualsCanonicalizing([$ids[3], $ids[4]], array_slice($seen, 3), $direction);
        }
    }

    public function test_total_counts_matching_layers_not_the_page()
    {
        for ($n = 0; $n < 5; $n++) {
            Fixtures::layer();
        }
        $r = $this->catalogue('per_page=2');
        $this->assertSame(5, $r->json('total'));
        $this->assertSame(2, $r->json('page.returned'));
        $this->assertNotNull($r->json('page.next_cursor'));
        $this->assertStringStartsWith('http://localhost/api/v2/layers?', $r->json('page.next'));
    }

    public function test_figures_are_computed_for_the_returned_page_only()
    {
        $ids = [];
        foreach ([[1850, -33.0], [1860, -32.0], [1870, -31.0]] as $n => [$year, $lat]) {
            $ids[$n] = Fixtures::layer();
            Fixtures::record(['dataset_id' => $ids[$n], 'lat' => $lat, 'lon' => 151.0, 'datestart' => (string) $year]);
            Fixtures::record(['dataset_id' => $ids[$n]]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $r = $this->catalogue('per_page=1');
        $log = DB::getQueryLog();
        DB::disableQueryLog();

        // Date functions run in one query only, over the records of the page's one layer.
        $dated = array_values(array_filter($log, function (array $q) {
            return strpos($q['query'], 'v2_udate') !== false;
        }));
        $this->assertCount(1, $dated);
        $this->assertStringContainsString('"i"."dataset_id" in (?)', $dated[0]['query']);
        $this->assertContains($ids[0], $dated[0]['bindings']);
        $this->assertNotContains($ids[1], $dated[0]['bindings']);
        $this->assertSame(3, $r->json('total'));

        // Each page carries its own layer's figures.
        foreach ($ids as $n => $id) {
            $this->assertSame([$id], $this->ids($r));
            $layer = $r->json('layers.0');
            $this->assertSame(2, $layer['record_count']);
            $this->assertSame([151, -33 + $n, 151, -33 + $n], $layer['bbox']);
            $this->assertSame(['start' => Civil::udate(1850 + 10 * $n), 'end' => Civil::udate(1851 + 10 * $n) - 1], $layer['date_range']);
            $this->assertSame(['total' => 1, 'no_date' => 1, 'unparseable_date' => 0], $layer['undated']);
            if ($n < 2) {
                $r = $this->get($r->json('page.next'));
                $r->assertStatus(200);
            }
        }
    }

    public function test_min_records_counts_without_date_functions_and_keeps_empty_layers_at_zero()
    {
        $empty = Fixtures::layer();
        $one = Fixtures::layer();
        Fixtures::record(['dataset_id' => $one, 'datestart' => '1850']);

        $this->assertSame([$empty, $one], $this->ids($this->catalogue('min_records=0')));
        $r = $this->catalogue('min_records=1');
        $this->assertSame([$one], $this->ids($r));
        $this->assertSame(1, $r->json('total'));
        $this->assertSame(1, $r->json('layers.0.record_count'));
        $this->assertSame(0, $this->catalogue('min_records=2')->json('total'));
    }
}
