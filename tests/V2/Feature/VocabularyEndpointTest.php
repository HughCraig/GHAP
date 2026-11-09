<?php

namespace Tests\V2\Feature;

use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/** GET /api/v2/vocabulary (A2 spec §5). */
class VocabularyEndpointTest extends TestCase
{
    private const ECHO_KEYS = [
        'field', 'name', 'match', 'sources', 'layers', 'bbox', 'polygon', 'date_from', 'date_to',
        'include_undated', 'state', 'lga', 'parish', 'feature_term', 'record_type', 'extended_data',
        'sort', 'direction', 'cursor', 'per_page',
    ];

    private function assertError($response, int $status, string $code): void
    {
        $response->assertStatus($status);
        $this->assertSame($code, $response->json('error.code'));
    }

    private function vocab(string $qs)
    {
        return $this->get('/api/v2/vocabulary?' . $qs);
    }

    /** @param array<string,int> $counts feature_term => number of records */
    private function terms(int $layer, array $counts): void
    {
        foreach ($counts as $term => $n) {
            for ($i = 0; $i < $n; $i++) {
                Fixtures::record(['dataset_id' => $layer, 'feature_term' => (string) $term]);
            }
        }
    }

    /** Follows page.next to the end; returns every page's response. */
    private function walk(string $qs): array
    {
        $pages = [];
        $r = $this->vocab($qs);
        for ($guard = 0; $guard < 100; $guard++) {
            $r->assertStatus(200);
            $pages[] = $r;
            $next = $r->json('page.next');
            if ($next === null) {
                return $pages;
            }
            $parts = parse_url($next);
            $r = $this->get($parts['path'] . '?' . $parts['query']);
        }
        $this->fail('The pages did not end.');
    }

    private function values(array $pages): array
    {
        $values = [];
        foreach ($pages as $r) {
            foreach ($r->json('values') as $entry) {
                $values[] = $entry['value'];
            }
        }
        return $values;
    }

    public function test_response_shape_and_echo()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, ['bay' => 2, 'hill' => 1]);

        $r = $this->vocab('field=feature_term&layers=' . $layer);
        $r->assertStatus(200);
        $this->assertSame(['query', 'total', 'records', 'page', 'values'], array_keys($r->json()));
        $this->assertSame(self::ECHO_KEYS, array_keys($r->json('query')));
        $q = $r->json('query');
        $this->assertSame('feature_term', $q['field']);
        $this->assertSame('value', $q['sort']);
        $this->assertSame('asc', $q['direction']);
        $this->assertNull($q['cursor']);
        $this->assertSame(1000, $q['per_page']);
        $this->assertSame(2, $r->json('total'));
        $this->assertSame(['total' => 3, 'with_value' => 3, 'without_value' => 0], $r->json('records'));
        $this->assertSame(
            ['returned', 'per_page', 'next_cursor', 'next'],
            array_keys($r->json('page'))
        );
        $this->assertSame('bay', $r->json('values.0.value'));
        $this->assertSame(2, $r->json('values.0.record_count'));
    }

    public function test_totals_describe_the_whole_match_set_on_every_page()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, ['a' => 1, 'b' => 1, 'c' => 1, 'd' => 1, 'e' => 1]);
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => '  ']);

        $pages = $this->walk('field=feature_term&per_page=2&layers=' . $layer);
        $this->assertCount(3, $pages);
        foreach ($pages as $r) {
            $this->assertSame(5, $r->json('total'));
            $this->assertSame(['total' => 6, 'with_value' => 5, 'without_value' => 1], $r->json('records'));
        }
        $this->assertSame(['a', 'b', 'c', 'd', 'e'], $this->values($pages));
    }

    public function test_every_entry_once_for_each_sort_and_direction()
    {
        $layer = Fixtures::layer();
        $counts = ['g' => 1, 'b' => 3, 'c' => 3, 'a' => 3, 'e' => 2, 'f' => 1, 'd' => 5];
        $this->terms($layer, $counts);
        $keys = array_keys($counts);

        $byValue = $keys;
        sort($byValue, SORT_STRING);
        $byCount = $keys;
        usort($byCount, function ($x, $y) use ($counts) {
            return [$counts[$x], $x] <=> [$counts[$y], $y];
        });

        $expected = [
            'value asc' => $byValue,
            'value desc' => array_reverse($byValue),
            'record_count asc' => $byCount,
            'record_count desc' => array_reverse($byCount),
        ];
        foreach ($expected as $label => $order) {
            list($sort, $direction) = explode(' ', $label);
            $pages = $this->walk(
                "field=feature_term&per_page=2&sort=$sort&direction=$direction&layers=$layer"
            );
            $this->assertSame($order, $this->values($pages), $label);
        }
    }

    public function test_values_that_need_encoding_survive_next()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, [
            "Break O'Day" => 1, 'Sand, Gravel' => 1, 'A&B' => 1, 'C+D' => 1, '50%' => 1, 'ÉCOLE' => 1,
        ]);
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => 'école']);

        $pages = $this->walk('field=feature_term&per_page=1&layers=' . $layer);
        $this->assertCount(6, $pages);
        $this->assertSame(
            ['50%', 'A&B', "Break O'Day", 'C+D', 'Sand, Gravel', 'ÉCOLE'],
            $this->values($pages)
        );
        $last = end($pages);
        $this->assertSame(2, $last->json('values.0.record_count'));
    }

    public function test_paged_request_with_bound_filters()
    {
        $layer = Fixtures::layer();
        for ($i = 0; $i < 4; $i++) {
            Fixtures::record([
                'dataset_id' => $layer, 'feature_term' => 't' . $i, 'state' => 'NSW',
                'lat' => -32.7, 'lon' => 151.2,
            ]);
        }
        Fixtures::record([
            'dataset_id' => $layer, 'feature_term' => 'outside', 'state' => 'NSW',
            'lat' => 51.5, 'lon' => -0.1,
        ]);

        $pages = $this->walk(
            'field=feature_term&per_page=2&state=NSW&bbox=150.8,-33,151.6,-32.4&layers=' . $layer
            . '&sort=record_count&direction=desc'
        );
        $this->assertSame(['t3', 't2', 't1', 't0'], $this->values($pages));
        $this->assertSame(4, $pages[1]->json('total'));
    }

    public function test_cursor_from_elsewhere_is_rejected()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, ['a' => 1, 'b' => 1, 'c' => 1]);

        $place = $this->get('/api/v2/places?layers=' . $layer . '&per_page=1')->json('page.next_cursor');
        $mine = $this->vocab('field=feature_term&per_page=1&layers=' . $layer)->json('page.next_cursor');
        $this->assertNotNull($place);
        $this->assertNotNull($mine);

        $this->vocab('field=feature_term&per_page=1&layers=' . $layer . '&cursor=' . $mine)->assertStatus(200);

        foreach ([
            'field=feature_term&layers=' . $layer . '&cursor=' . $place,
            'field=lga&per_page=1&layers=' . $layer . '&cursor=' . $mine,
            'field=feature_term&per_page=1&sort=record_count&layers=' . $layer . '&cursor=' . $mine,
        ] as $qs) {
            $r = $this->vocab($qs);
            $this->assertError($r, 400, 'INVALID_PARAMETER');
            $this->assertSame('cursor', $r->json('error.details.parameter'), $qs);
        }
    }

    public function test_parameter_errors()
    {
        $r = $this->vocab('');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('required', $r->json('error.details.rule'));

        $r = $this->vocab('field=x');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('one_of', $r->json('error.details.rule'));

        $r = $this->vocab('field=');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('empty', $r->json('error.details.rule'));

        $r = $this->vocab('field=lga&field=state');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('repeated', $r->json('error.details.rule'));

        $r = $this->vocab('field=lga&direction=sideways');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('one_of', $r->json('error.details.rule'));

        $r = $this->vocab('field=lga&sort=title');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('one_of', $r->json('error.details.rule'));

        $r = $this->vocab('field=lga&nulls=last');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('unknown', $r->json('error.details.rule'));

        $r = $this->vocab('field=lga&limit=5');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('unknown', $r->json('error.details.rule'));

        $r = $this->vocab('field=lga&per_page=10001');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('range', $r->json('error.details.rule'));

        $private = Fixtures::layer(['public' => false]);
        $this->assertError($this->vocab('field=lga&layers=' . $private), 403, 'LAYER_PRIVATE');
        $this->assertError($this->vocab('field=lga&layers=1'), 404, 'LAYER_NOT_FOUND');
        $this->assertError(
            $this->vocab('field=lga&extended_data=Years%20%3E%20many'),
            400,
            'FILTER_REJECTED'
        );

        $this->post('/api/v2/vocabulary')->assertStatus(405);

        $r = $this->call('OPTIONS', '/api/v2/vocabulary');
        $r->assertStatus(204);
        $r->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_filtering_on_the_listed_field_yields_at_most_one_entry()
    {
        $layer = Fixtures::layer();
        foreach (['CESSNOCK', 'Cessnock', 'Maitland'] as $lga) {
            Fixtures::record(['dataset_id' => $layer, 'lga' => $lga]);
        }

        $r = $this->vocab('field=lga&lga=cessnock&layers=' . $layer);
        $r->assertStatus(200);
        $this->assertSame(1, $r->json('total'));
        $this->assertCount(1, $r->json('values'));
        $this->assertSame(2, $r->json('values.0.record_count'));
    }
}
