<?php

namespace Tests\V2\Feature;

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
        fwrite(STDERR, sprintf(
            "\nstatistics area_km2 computed=%.9F expected=%.9F centroid=[%.9F, %.9F]\n",
            $s['area_km2'], $expected, $s['centroid'][0], $s['centroid'][1]
        ));
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
        fwrite(STDERR, sprintf(
            "\ncentroid computed=[%.9F, %.9F] expected(sphere)=[%.9F, %.9F]\n", $c[0], $c[1], $expLon, $expLat
        ));
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
}
