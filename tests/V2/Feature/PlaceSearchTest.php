<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Civil;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/**
 * GET /api/v2/places, tested over HTTP. Each test is prefixed with the DESIGN item it pins.
 */
class PlaceSearchTest extends TestCase
{
    /** Every parameter of spec §10.1, in echo order. */
    private const ECHO_KEYS = [
        'name', 'match', 'sources', 'layers', 'bbox', 'polygon', 'date_from', 'date_to',
        'include_undated', 'state', 'lga', 'feature_term', 'record_type', 'extended_data',
        'summary_only', 'sort', 'direction', 'nulls', 'limit', 'sample', 'seed', 'cursor', 'per_page',
    ];

    private function search(string $qs = '')
    {
        return $this->get('/api/v2/places' . ($qs === '' ? '' : '?' . $qs));
    }

    /** The feature ids (uids) of a response, in order. */
    private function uids($response): array
    {
        return array_map(function (array $f) {
            return $f['properties']['id'];
        }, $response->json('features'));
    }

    private function uid(int $id): string
    {
        return (string) DB::table('tlcmap.dataitem')->where('id', $id)->value('uid');
    }

    /** Follows page.next to the end; returns [uids in order, returned count per page, responses]. */
    private function pageAll(string $qs): array
    {
        $uids = [];
        $counts = [];
        $responses = [];
        $r = $this->search($qs);
        while (true) {
            $r->assertStatus(200);
            $responses[] = $r;
            $uids = array_merge($uids, $this->uids($r));
            $counts[] = $r->json('page.returned');
            $next = $r->json('page.next');
            if ($next === null) {
                $this->assertNull($r->json('page.next_cursor'));
                break;
            }
            $this->assertNotNull($r->json('page.next_cursor'));
            $this->assertLessThan(50, count($responses), 'Paging did not end.');
            $r = $this->get($next);
        }
        return [$uids, $counts, $responses];
    }

    private function assertError($response, int $status, string $code): void
    {
        $response->assertStatus($status);
        $this->assertSame($code, $response->json('error.code'));
    }

    // ② -----------------------------------------------------------------------------------------

    public function test_2_no_response_is_a_redirect()
    {
        $private = Fixtures::layer(['public' => false]);
        Fixtures::record(['title' => 'Wollombi']);

        $requests = [
            '',
            'name=Wollombi&match=fuzzy',
            'datefrom=1850',
            'extended_data=Years',
            'layers=' . $private,
            'layers=999999999',
            'per_page=6000',
            'bbox=',
            'sort=start&nulls=first&per_page=1',
            'sample=3&seed=x',
        ];
        $this->assertCount(10, $requests);
        foreach ($requests as $qs) {
            $status = $this->search($qs)->getStatusCode();
            $this->assertFalse($status >= 300 && $status < 400, "'$qs' answered $status.");
        }
    }

    public function test_2_unknown_parameter_is_400()
    {
        $r = $this->search('datefrom=1850');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('datefrom', $r->json('error.details.parameter'));
    }

    public function test_2_bad_extended_data_is_400()
    {
        $r = $this->search('extended_data=Years');
        $this->assertError($r, 400, 'FILTER_REJECTED');
        $this->assertSame(5, $r->json('error.details.position'));
    }

    public function test_2_private_layer_is_403()
    {
        $private = Fixtures::layer(['public' => false]);
        $r = $this->search('layers=' . $private);
        $this->assertError($r, 403, 'LAYER_PRIVATE');
        $this->assertSame($private, $r->json('error.details.layer_id'));
    }

    // ③ -----------------------------------------------------------------------------------------

    public function test_3_pages_beyond_any_ceiling()
    {
        $layer = Fixtures::layer();
        $expected = [];
        for ($n = 0; $n < 1203; $n++) {
            $expected[] = $this->uid(Fixtures::record(['dataset_id' => $layer]));
        }

        list($uids, $counts, $responses) = $this->pageAll('per_page=500');

        $this->assertSame([500, 500, 203], $counts);
        $this->assertSame($expected, $uids, 'Every record once, in id order.');
        foreach ($responses as $r) {
            $this->assertSame(1203, $r->json('summary.total'));
            $this->assertSame(500, $r->json('page.per_page'));
        }
    }

    public function test_3_cursor_bound_to_query()
    {
        for ($n = 0; $n < 3; $n++) {
            Fixtures::record(['title' => 'other']);
        }
        $cursor = $this->search('name=other&per_page=1')->json('page.next_cursor');
        $this->assertNotNull($cursor);

        $this->search('name=other&per_page=1&cursor=' . $cursor)->assertStatus(200);

        $r = $this->search('per_page=1&cursor=' . $cursor);
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('cursor', $r->json('error.details.parameter'));

        $r = $this->search('name=other&match=contains&per_page=1&cursor=' . $cursor);
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('cursor', $r->json('error.details.parameter'));

        $r = $this->search('cursor=not-a-cursor');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('cursor', $r->json('error.details.parameter'));
    }

    public function test_3_per_page_may_change_between_pages()
    {
        $all = [];
        for ($n = 0; $n < 5; $n++) {
            $all[] = $this->uid(Fixtures::record());
        }

        $first = $this->search('per_page=2');
        $this->assertSame(array_slice($all, 0, 2), $this->uids($first));

        $second = $this->search('per_page=3&cursor=' . $first->json('page.next_cursor'));
        $second->assertStatus(200);
        $this->assertSame(array_slice($all, 2, 3), $this->uids($second));
        $this->assertSame(3, $second->json('page.per_page'));
        $this->assertNull($second->json('page.next_cursor'));
        $this->assertNull($second->json('page.next'));
    }

    public function test_3_every_sort_and_nulls_visits_each_record_once_in_order()
    {
        $rows = [
            ['Moree', '1850', '1860'], ['alpha', null, null], ['Bega', '1820', null],
            ['Alpha', null, '1830'], ['Dubbo', 'junk', null], ['Bega', '1850', '1855'],
            ['Cessnock', '1900', '1700'], ['Zeehan', null, null], ['Inverell', '1850', null],
        ];
        $all = [];
        foreach ($rows as $row) {
            $all[] = $this->uid(Fixtures::record(['title' => $row[0], 'datestart' => $row[1], 'dateend' => $row[2]]));
        }
        sort($all);

        foreach (['id', 'title', 'start', 'end'] as $sort) {
            foreach (['asc', 'desc'] as $direction) {
                foreach (['first', 'last'] as $nulls) {
                    // Sixteen orders take more requests than the v2 rate limit allows a minute.
                    Cache::flush();
                    $qs = "sort=$sort&direction=$direction&nulls=$nulls";
                    $whole = $this->uids($this->search($qs));
                    list($paged) = $this->pageAll($qs . '&per_page=2');

                    $this->assertSame($whole, $paged, $qs);
                    $sorted = $paged;
                    sort($sorted);
                    $this->assertSame($all, $sorted, $qs);
                }
            }
        }
    }

    // ④ -----------------------------------------------------------------------------------------

    public function test_4_summary_describes_match_set_not_page()
    {
        $a = Fixtures::layer(['name' => 'Alpha']);
        $b = Fixtures::layer(['name' => 'Beta', 'warning' => 'None']);
        Fixtures::record(['dataset_id' => $a]);
        Fixtures::record(['dataset_id' => $a]);
        Fixtures::record(['dataset_id' => $a]);
        Fixtures::record(['dataset_id' => $b]);
        Fixtures::record([]);

        $paged = $this->search('per_page=2');
        $whole = $this->search('per_page=5000');

        $this->assertSame(2, $paged->json('page.returned'));
        $this->assertSame(5, $paged->json('summary.total'));
        $this->assertSame([
            ['layer_id' => $a, 'name' => 'Alpha', 'count' => 3, 'warning' => null],
            ['layer_id' => $b, 'name' => 'Beta', 'count' => 1, 'warning' => 'None'],
        ], $paged->json('summary.by_layer'));
        $this->assertSame($whole->json('summary'), $paged->json('summary'));
    }

    public function test_4_summary_only()
    {
        Fixtures::record([]);
        Fixtures::record([]);

        $r = $this->search('summary_only=true');
        $r->assertStatus(200);
        $this->assertSame(['query', 'summary'], array_keys($r->json()));
        $this->assertTrue($r->json('query.summary_only'));
        $this->assertSame(2, $r->json('summary.total'));
        $this->assertSame([], $r->json('summary.by_layer'));
    }

    // ⑤ -----------------------------------------------------------------------------------------

    public function test_5_extended_data_namespaced()
    {
        Fixtures::record([
            'description' => 'Built-in description',
            'extended_data' => '<ExtendedData><Data name="description"><value>Contributor description</value></Data>'
                . '<Data name="source"><value>Contributor source</value></Data></ExtendedData>',
            'source' => 'Built-in source',
        ]);

        $p = $this->search()->json('features.0.properties');
        $this->assertSame('Built-in description', $p['description']);
        $this->assertSame('Built-in source', $p['source']);
        $this->assertSame(
            ['description' => 'Contributor description', 'source' => 'Contributor source'],
            $p['extended_data']
        );
    }

    // ⑥ -----------------------------------------------------------------------------------------

    public function test_6_udateend_from_dateend()
    {
        Fixtures::record(['datestart' => '1850', 'dateend' => '1860']);

        $p = $this->search()->json('features.0.properties');
        $this->assertSame(Civil::udate(1850), $p['udatestart']);
        $this->assertSame(Civil::udate(1860), $p['udateend']);
    }

    // ⑦ -----------------------------------------------------------------------------------------

    public function test_7_sort_keeps_undated()
    {
        $late = $this->uid(Fixtures::record(['datestart' => '1900']));
        $noDate = $this->uid(Fixtures::record([]));
        $early = $this->uid(Fixtures::record(['datestart' => '1850']));
        $junk = $this->uid(Fixtures::record(['datestart' => 'junk']));

        $this->assertSame([$early, $late, $noDate, $junk], $this->uids($this->search('sort=start')));
        $this->assertSame([$noDate, $junk, $early, $late], $this->uids($this->search('sort=start&nulls=first')));
        $this->assertSame([$late, $early, $junk, $noDate], $this->uids($this->search('sort=end&direction=desc')));
    }

    public function test_7_date_filter_keeps_undated_by_default()
    {
        $in = $this->uid(Fixtures::record(['datestart' => '1850']));
        Fixtures::record(['datestart' => '1900']);
        $undated = $this->uid(Fixtures::record([]));

        $r = $this->search('date_from=1840&date_to=1860');
        $this->assertSame([$in, $undated], $this->uids($r));
        $this->assertTrue($r->json('query.include_undated'));
        $this->assertSame(1, $r->json('summary.undated.total'));
    }

    public function test_7_include_undated_false_drops_them_and_summary_says_so()
    {
        $in = $this->uid(Fixtures::record(['datestart' => '1850']));
        Fixtures::record(['datestart' => '1900']);
        Fixtures::record([]);
        Fixtures::record(['datestart' => 'junk']);

        $r = $this->search('date_from=1840&date_to=1860&include_undated=false');
        $this->assertSame([$in], $this->uids($r));
        $this->assertFalse($r->json('query.include_undated'));
        $this->assertSame(1, $r->json('summary.total'));
        $this->assertSame(['total' => 0, 'no_date' => 0, 'unparseable_date' => 0], $r->json('summary.undated'));
    }

    // ⑧ -----------------------------------------------------------------------------------------

    public function test_8_limit_is_deterministic()
    {
        foreach (['Moree', 'Bega', 'Dubbo', 'Alpha', 'Zeehan', 'Bega', 'Cessnock', 'Kempsey'] as $title) {
            Fixtures::record(['title' => $title]);
        }

        $first = $this->search('limit=5&sort=title');
        $second = $this->search('limit=5&sort=title');
        $unlimited = $this->search('sort=title');

        $this->assertCount(5, $this->uids($first));
        $this->assertSame($this->uids($first), $this->uids($second));
        $this->assertSame(array_slice($this->uids($unlimited), 0, 5), $this->uids($first));
        $this->assertSame(5, $first->json('query.limit'));
        $this->assertSame(8, $first->json('summary.total'));
        $this->assertNull($first->json('page.next_cursor'));
    }

    public function test_8_limit_across_pages()
    {
        $all = [];
        for ($n = 0; $n < 10; $n++) {
            $all[] = $this->uid(Fixtures::record());
        }

        list($uids, $counts, $responses) = $this->pageAll('limit=7&per_page=3');

        $this->assertSame([3, 3, 1], $counts);
        $this->assertSame(array_slice($all, 0, 7), $uids);
        foreach ($responses as $r) {
            $this->assertSame(10, $r->json('summary.total'));
        }
    }

    public function test_8_sample_repeatable_with_seed()
    {
        $ids = [];
        for ($n = 0; $n < 20; $n++) {
            $ids[] = Fixtures::record();
        }

        $first = $this->search('sample=5&seed=abc');
        $second = $this->search('sample=5&seed=abc');
        $first->assertStatus(200);

        $this->assertCount(5, $this->uids($first));
        $this->assertSame($this->uids($first), $this->uids($second));
        $this->assertSame('abc', $first->json('query.seed'));
        $this->assertSame(5, $first->json('query.sample'));
        $this->assertSame(20, $first->json('summary.total'));
        $this->assertSame(
            ['returned' => 5, 'per_page' => 500, 'next_cursor' => null, 'next' => null],
            $first->json('page')
        );

        // The order is md5(id || seed), computed here independently of the database.
        usort($ids, function (int $x, int $y) {
            return strcmp(md5($x . 'abc'), md5($y . 'abc'));
        });
        $this->assertSame(array_map([$this, 'uid'], array_slice($ids, 0, 5)), $this->uids($first));

        $unseeded = $this->search('sample=5');
        $seed = $unseeded->json('query.seed');
        $this->assertIsString($seed);
        $this->assertNotSame('', $seed);
        $again = $this->search('sample=5&seed=' . rawurlencode($seed));
        $this->assertSame($this->uids($unseeded), $this->uids($again));

        $this->assertCount(20, $this->uids($this->search('sample=50&seed=abc')), 'Fewer matches than N returns them all.');
    }

    public function test_8_sample_conflicts_with_sort()
    {
        $r = $this->search('sample=5&sort=title');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('conflicts', $r->json('error.details.rule'));
    }

    // Shared ------------------------------------------------------------------------------------

    public function test_query_echo_has_every_parameter_with_defaults()
    {
        $q = $this->search()->json('query');
        $this->assertSame(self::ECHO_KEYS, array_keys($q));
        $this->assertSame([
            'name' => null,
            'match' => null,
            'sources' => ['layers', 'anps', 'ncg', 'text'],
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
            'summary_only' => false,
            'sort' => 'id',
            'direction' => 'asc',
            'nulls' => 'last',
            'limit' => null,
            'sample' => null,
            'seed' => null,
            'cursor' => null,
            'per_page' => 500,
        ], $q);

        // `match` has an effective value only with a name (spec §7.3), and then defaults to exact.
        $q = $this->search('name=Wollombi')->json('query');
        $this->assertSame(self::ECHO_KEYS, array_keys($q));
        $this->assertSame('Wollombi', $q['name']);
        $this->assertSame('exact', $q['match']);

        $layer = Fixtures::layer();
        $q = $this->search('sources=ncg,anps&layers=' . $layer . '&bbox=150,-34,151,-33&per_page=7&limit=3')
            ->json('query');
        $this->assertSame(['anps', 'ncg'], $q['sources']);
        $this->assertSame([$layer], $q['layers']);
        // JSON has one number type; whole degrees may decode as integers.
        $this->assertEquals([150.0, -34.0, 151.0, -33.0], $q['bbox']);
        $this->assertSame(7, $q['per_page']);
        $this->assertSame(3, $q['limit']);
    }

    public function test_cursor_echoed_as_given()
    {
        Fixtures::record();
        Fixtures::record();
        $cursor = $this->search('per_page=1')->json('page.next_cursor');
        $this->assertSame($cursor, $this->search('per_page=1&cursor=' . $cursor)->json('query.cursor'));
    }

    public function test_next_is_this_request_with_the_cursor_set()
    {
        for ($n = 0; $n < 3; $n++) {
            Fixtures::record(['title' => 'Two words']);
        }
        $first = $this->search('name=Two%20words&per_page=1&sort=title');
        $cursor = $first->json('page.next_cursor');
        $this->assertSame(
            'http://localhost/api/v2/places?name=Two%20words&per_page=1&sort=title&cursor=' . $cursor,
            $first->json('page.next')
        );
        $second = $this->get($first->json('page.next'));
        $next = $second->json('page.next_cursor');
        $this->assertSame(
            'http://localhost/api/v2/places?name=Two%20words&per_page=1&sort=title&cursor=' . $next,
            $second->json('page.next')
        );
    }

    public function test_records_without_coordinates_have_null_geometry()
    {
        Fixtures::record(['lon' => 151.14, 'lat' => -32.94]);
        Fixtures::record([]);

        $features = $this->search()->json('features');
        $this->assertSame(['type' => 'Point', 'coordinates' => [151.14, -32.94]], $features[0]['geometry']);
        $this->assertArrayHasKey('geometry', $features[1]);
        $this->assertNull($features[1]['geometry']);
        $this->assertSame(1, $this->search()->json('summary.no_coordinates_count'));
    }

    public function test_empty_search_pages_everything()
    {
        $public = Fixtures::layer();
        $private = Fixtures::layer(['public' => false]);
        $visible = [];
        $visible[] = $this->uid(Fixtures::record(['dataset_id' => $public]));
        Fixtures::record(['dataset_id' => $private]);
        $visible[] = $this->uid(Fixtures::record([]));
        $visible[] = $this->uid(Fixtures::record(['dataset_id' => $public]));
        Fixtures::record(['dataset_id' => $private]);
        $visible[] = $this->uid(Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchncg')]));

        list($uids, , $responses) = $this->pageAll('per_page=2');
        $this->assertSame($visible, $uids);
        $this->assertSame(4, $responses[0]->json('summary.total'));
    }

    public function test_response_shape()
    {
        Fixtures::record();

        $r = $this->search();
        $r->assertStatus(200);
        $r->assertHeader('Content-Type', 'application/json; charset=utf-8');
        $this->assertSame('FeatureCollection', $r->json('type'));
        $this->assertSame(['type', 'query', 'summary', 'page', 'features'], array_keys($r->json()));
        $this->assertSame(['returned', 'per_page', 'next_cursor', 'next'], array_keys($r->json('page')));
        $this->assertSame('Feature', $r->json('features.0.type'));
        $this->assertStringNotContainsString("\n", $r->getContent());
        $this->assertStringContainsString('"url":"http://localhost/places/', $r->getContent());
    }
}
