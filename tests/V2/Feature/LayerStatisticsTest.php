<?php

namespace Tests\V2\Feature;

use Tests\V2\Support\Civil;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/** GET /api/v2/layers/{id}/statistics (spec §6): shape, errors and the spatial figures. */
class LayerStatisticsTest extends TestCase
{
    private const STATISTICS_KEYS = [
        'count', 'basis', 'extent', 'centroid', 'convex_hull', 'area_km2', 'density_per_km2',
        'date_range', 'median_date', 'mean_date', 'undated', 'unparseable_dates', 'feature_terms',
    ];

    private function assertError($r, int $status, string $code): void
    {
        $r->assertStatus($status);
        $this->assertSame($code, $r->json('error.code'));
    }

    private function point(int $layer, float $lon, float $lat): void
    {
        Fixtures::record(['dataset_id' => $layer, 'lat' => $lat, 'lon' => $lon]);
    }

    /** WGS84 area in km2 of a cell dLon x dLat degrees centred at latitude $lat. */
    private function cellKm2(float $dLon, float $dLat, float $lat): float
    {
        $a = 6378137.0;
        $e2 = 0.00669437999014;
        $phi = deg2rad($lat);
        $w = 1 - $e2 * sin($phi) ** 2;
        $east = (M_PI / 180) * $a * cos($phi) / sqrt($w) * $dLon;
        $north = (M_PI / 180) * $a * (1 - $e2) / ($w ** 1.5) * $dLat;

        return $east * $north / 1e6;
    }

    public function test_shape_and_layer_identity()
    {
        $id = Fixtures::layer(['name' => 'Music', 'warning' => 'None']);
        $r = $this->get('/api/v2/layers/' . $id . '/statistics');
        $r->assertStatus(200);
        $this->assertSame(['layer', 'statistics'], array_keys($r->json()));
        $this->assertSame(['id', 'name', 'warning', 'url'], array_keys($r->json('layer')));
        $this->assertSame($id, $r->json('layer.id'));
        $this->assertSame('Music', $r->json('layer.name'));
        $this->assertSame('None', $r->json('layer.warning'));
        $this->assertSame(url('layers/' . $id), $r->json('layer.url'));
        $this->assertSame(self::STATISTICS_KEYS, array_keys($r->json('statistics')));
        $this->assertSame(['records', 'with_coordinates', 'dated'], array_keys($r->json('statistics.basis')));

        $blank = Fixtures::layer(['warning' => '  ']);
        $this->assertNull($this->get('/api/v2/layers/' . $blank . '/statistics')->json('layer.warning'));

        $advisory = "Advisory:  may contain names \n";
        $adv = Fixtures::layer(['warning' => $advisory]);
        $this->assertSame($advisory, $this->get('/api/v2/layers/' . $adv . '/statistics')->json('layer.warning'));
    }

    public function test_errors()
    {
        $private = Fixtures::layer(['public' => false]);
        $this->assertError($this->get('/api/v2/layers/' . $private . '/statistics'), 403, 'LAYER_PRIVATE');
        $this->assertError($this->get('/api/v2/layers/1/statistics'), 404, 'LAYER_NOT_FOUND');
        $this->assertError($this->get('/api/v2/layers/abc/statistics'), 404, 'NOT_FOUND');

        $id = Fixtures::layer();
        $r = $this->get('/api/v2/layers/' . $id . '/statistics?x=1');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('unknown', $r->json('error.details.rule'));
    }

    public function test_spatial_figures_against_independent_values()
    {
        $id = Fixtures::layer();
        $this->point($id, 151.0, -33.0);
        $this->point($id, 151.01, -33.0);
        $this->point($id, 151.01, -33.01);
        $this->point($id, 151.0, -33.01);
        $this->point($id, 151.005, -33.005);
        Fixtures::record(['dataset_id' => $id]);

        $s = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics');
        $this->assertSame(6, $s['count']);
        $this->assertSame(6, $s['basis']['records']);
        $this->assertSame(5, $s['basis']['with_coordinates']);
        $this->assertEqualsWithDelta([151.0, -33.01, 151.01, -33.0], $s['extent'], 1e-9);
        $this->assertSame('Polygon', $s['convex_hull']['type']);
        $ring = $s['convex_hull']['coordinates'][0];
        $this->assertCount(5, $ring);
        $corners = [];
        foreach (array_slice($ring, 0, 4) as $c) {
            $corners[] = round($c[0], 6) . ',' . round($c[1], 6);
        }
        sort($corners);
        $this->assertSame(['151,-33', '151,-33.01', '151.01,-33', '151.01,-33.01'], $corners);

        $expected = $this->cellKm2(0.01, 0.01, -33.005);
        $this->assertEqualsWithDelta($expected, $s['area_km2'], $expected * 0.001);
        $this->assertEqualsWithDelta(151.005, $s['centroid'][0], 1e-4);
        $this->assertEqualsWithDelta(-33.005, $s['centroid'][1], 1e-4);
        $this->assertEqualsWithDelta(5 / $s['area_km2'], $s['density_per_km2'], 1e-9);
    }

    public function test_centroid_is_on_the_spheroid()
    {
        $id = Fixtures::layer();
        $this->point($id, 100.0, -30.0);
        $this->point($id, 160.0, -30.0);

        // Unit-sphere vector mean of the two points.
        $x = $y = $z = 0.0;
        foreach ([100.0, 160.0] as $lon) {
            $x += cos(deg2rad(-30)) * cos(deg2rad($lon));
            $y += cos(deg2rad(-30)) * sin(deg2rad($lon));
            $z += sin(deg2rad(-30));
        }
        $expLon = rad2deg(atan2($y, $x));
        $expLat = rad2deg(atan2($z, sqrt($x * $x + $y * $y)));

        $c = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics.centroid');
        $this->assertEqualsWithDelta($expLon, $c[0], 0.1);
        $this->assertEqualsWithDelta($expLat, $c[1], 0.1);
        $this->assertLessThan(-31.0, $c[1]);
    }

    public function test_point_and_line_hulls()
    {
        $one = Fixtures::layer();
        $this->point($one, 150.0, -30.0);
        $this->point($one, 150.0, -30.0);
        $s = $this->get('/api/v2/layers/' . $one . '/statistics')->json('statistics');
        $this->assertSame('Point', $s['convex_hull']['type']);
        $this->assertEquals(0, $s['area_km2']);
        $this->assertNull($s['density_per_km2']);

        $line = Fixtures::layer();
        $this->point($line, 150.0, -30.0);
        $this->point($line, 151.0, -30.0);
        $this->point($line, 152.0, -30.0);
        $s = $this->get('/api/v2/layers/' . $line . '/statistics')->json('statistics');
        $this->assertSame('LineString', $s['convex_hull']['type']);
        $this->assertEquals(0, $s['area_km2']);
        $this->assertNull($s['density_per_km2']);
    }

    public function test_no_coordinates_and_empty_layer()
    {
        $id = Fixtures::layer();
        Fixtures::record(['dataset_id' => $id]);
        Fixtures::record(['dataset_id' => $id]);
        $s = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics');
        $this->assertSame(2, $s['count']);
        $this->assertSame(0, $s['basis']['with_coordinates']);
        foreach (['extent', 'centroid', 'convex_hull', 'area_km2', 'density_per_km2'] as $key) {
            $this->assertNull($s[$key], $key);
        }

        $empty = Fixtures::layer();
        $s = $this->get('/api/v2/layers/' . $empty . '/statistics')->json('statistics');
        $this->assertSame(0, $s['count']);
        $this->assertSame(['records' => 0, 'with_coordinates' => 0, 'dated' => 0], $s['basis']);
        foreach (self::STATISTICS_KEYS as $key) {
            if (in_array($key, ['count', 'basis', 'undated', 'unparseable_dates', 'feature_terms'], true)) {
                continue;
            }
            $this->assertNull($s[$key], $key);
        }
        $this->assertSame(['total' => 0, 'no_date' => 0, 'unparseable_date' => 0], $s['undated']);
        $this->assertSame(0, $s['unparseable_dates']);
        $this->assertSame(['with_value' => 0, 'without_value' => 0, 'values' => []], $s['feature_terms']);
    }

    /** floor(n / d) toward negative infinity; intdiv truncates toward zero. */
    private static function floorDiv(int $n, int $d): int
    {
        $q = intdiv($n, $d);

        return ($n % $d !== 0 && ($n < 0) !== ($d < 0)) ? $q - 1 : $q;
    }

    /** floor((lo + hi) / 2) for a period's first and last millisecond. */
    private static function mid(int $lo, int $hi): int
    {
        return self::floorDiv($lo + $hi, 2);
    }

    public function test_midpoints_median_and_mean()
    {
        $id = Fixtures::layer();
        Fixtures::record(['dataset_id' => $id, 'lat' => -33.0, 'lon' => 151.0, 'datestart' => '1856']);
        Fixtures::record(['dataset_id' => $id, 'lat' => -33.5, 'lon' => 151.5, 'datestart' => '1820', 'dateend' => '1860']);
        Fixtures::record(['dataset_id' => $id, 'datestart' => '1900-03']);
        Fixtures::record(['dataset_id' => $id, 'lat' => -34.0, 'lon' => 152.0]);
        Fixtures::record(['dataset_id' => $id, 'lat' => -34.5, 'lon' => 152.5, 'datestart' => 'circa']);

        $s = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics');

        $m1856 = self::mid(Civil::udate(1856), Civil::udate(1857) - 1);
        $this->assertSame(-3581712000001, $m1856);
        $mRange = self::mid(Civil::udate(1820), Civil::udate(1861) - 1);
        $mMarch = self::mid(Civil::udate(1900, 3), Civil::udate(1900, 4) - 1);
        $mids = [$m1856, $mRange, $mMarch];
        sort($mids);

        $this->assertSame(5, $s['count']);
        $this->assertSame(3, $s['basis']['dated']);
        $this->assertSame(4, $s['basis']['with_coordinates']);
        $this->assertSame(['start' => Civil::udate(1820), 'end' => Civil::udate(1900, 4) - 1], $s['date_range']);
        $this->assertSame($mids[1], $s['median_date']);
        $this->assertSame(self::floorDiv(array_sum($mids), 3), $s['mean_date']);
        $this->assertSame(['total' => 2, 'no_date' => 1, 'unparseable_date' => 1], $s['undated']);
        $this->assertSame(1, $s['unparseable_dates']);
    }

    public function test_even_count_median_floors_the_mean_of_the_two_middle_values()
    {
        $id = Fixtures::layer();
        $mids = [];
        foreach ([1850, 1851, 1853, 1860] as $year) {
            Fixtures::record(['dataset_id' => $id, 'datestart' => (string) $year]);
            $mids[] = self::mid(Civil::udate($year), Civil::udate($year + 1) - 1);
        }
        sort($mids);

        $s = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics');
        $this->assertSame(self::floorDiv($mids[1] + $mids[2], 2), $s['median_date']);
        $this->assertSame(self::floorDiv(array_sum($mids), 4), $s['mean_date']);
        $this->assertTrue($s['median_date'] > $mids[1] && $s['median_date'] < $mids[2]);

        // One record: the median and the mean are its midpoint.
        $one = Fixtures::layer();
        Fixtures::record(['dataset_id' => $one, 'datestart' => '1850']);
        $s = $this->get('/api/v2/layers/' . $one . '/statistics')->json('statistics');
        $this->assertSame($mids[0], $s['median_date']);
        $this->assertSame($mids[0], $s['mean_date']);
    }

    public function test_bce_reversed_and_large_dates_are_exact_integers()
    {
        $id = Fixtures::layer();
        Fixtures::record(['dataset_id' => $id, 'datestart' => '-400']);
        Fixtures::record(['dataset_id' => $id, 'datestart' => '1900', 'dateend' => '1850']);
        Fixtures::record(['dataset_id' => $id, 'datestart' => '-200000']);

        // -400 is astronomical -399; the reversed record spans 1850 to the end of 1900.
        $mids = [
            self::mid(Civil::udate(-399), Civil::udate(-398) - 1),
            self::mid(Civil::udate(1850), Civil::udate(1901) - 1),
            self::mid(Civil::udate(-199999), Civil::udate(-199998) - 1),
        ];
        sort($mids);

        $s = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics');
        $this->assertSame(['start' => Civil::udate(-199999), 'end' => Civil::udate(1901) - 1], $s['date_range']);
        $this->assertTrue(is_int($s['median_date']));
        $this->assertTrue(is_int($s['mean_date']));
        $this->assertSame($mids[1], $s['median_date']);
        $this->assertSame(self::floorDiv(array_sum($mids), 3), $s['mean_date']);
        $this->assertLessThan(0, $s['mean_date']);
    }

    public function test_undated_counts_match_layer_detail()
    {
        $id = Fixtures::layer();
        Fixtures::record(['dataset_id' => $id, 'datestart' => '1850']);
        Fixtures::record(['dataset_id' => $id]);
        Fixtures::record(['dataset_id' => $id, 'datestart' => 'circa']);
        Fixtures::record(['dataset_id' => $id, 'datestart' => 'circa', 'dateend' => 'later']);

        $summary = $this->get('/api/v2/layers/' . $id)->json('summary');
        $s = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics');
        $this->assertSame($summary['undated'], $s['undated']);
        $this->assertSame($summary['unparseable_dates'], $s['unparseable_dates']);
        $this->assertSame($summary['date_range'], $s['date_range']);
        $this->assertSame(['total' => 3, 'no_date' => 1, 'unparseable_date' => 2], $s['undated']);
    }

    public function test_feature_terms_equal_the_layer_vocabulary()
    {
        $id = Fixtures::layer();
        foreach (['Hill', 'Hill', 'hill', 'Creek', '', '  '] as $term) {
            Fixtures::record(['dataset_id' => $id, 'feature_term' => $term]);
        }

        $terms = $this->get('/api/v2/layers/' . $id . '/statistics')->json('statistics.feature_terms');
        $this->assertSame(['with_value', 'without_value', 'values'], array_keys($terms));
        $this->assertSame(4, $terms['with_value']);
        $this->assertSame(2, $terms['without_value']);
        $this->assertSame(['hill', 'creek'], array_map('strtolower', array_column($terms['values'], 'value')));
        $this->assertSame([3, 1], array_column($terms['values'], 'record_count'));

        $walked = [];
        $base = '/api/v2/vocabulary?field=feature_term&per_page=1&layers=' . $id;
        $url = $base;
        for ($i = 0; $i < 10 && $url !== null; $i++) {
            $page = $this->get($url)->json();
            $walked = array_merge($walked, $page['values']);
            $cursor = $page['page']['next_cursor'];
            $url = $cursor === null ? null : $base . '&cursor=' . rawurlencode($cursor);
        }
        usort($walked, function ($a, $b) {
            return [$b['record_count'], $a['value']] <=> [$a['record_count'], $b['value']];
        });
        $this->assertSame($walked, $terms['values']);
    }
}
