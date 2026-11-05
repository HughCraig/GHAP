<?php

namespace Tests\V2\Feature;

use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/**
 * The catalogue lists exactly the layers that the equivalent place search draws records from
 * (spec §3.2, §10.2): the property DESIGN §9.4's recall and precision depend on.
 */
class CatalogueSearchInvariantTest extends TestCase
{
    private const BBOX = '150.8,-33,151.6,-32.4';
    private const POLYGON = '150.8 -33,151.6 -33,151.2 -32.4';

    /** In the box and the polygon. */
    private const IN = ['lat' => -32.7, 'lon' => 151.2];
    /** In the box, outside the polygon. */
    private const BOX_ONLY = ['lat' => -32.5, 'lon' => 150.9];
    private const OUT = ['lat' => -35.0, 'lon' => 149.0];

    /** @var int[] the public layers */
    private $public = [];

    private function fixture(): void
    {
        $inside = Fixtures::layer(['name' => 'Inside, dated']);
        Fixtures::record(['dataset_id' => $inside, 'datestart' => '1850'] + self::IN);
        Fixtures::record(['dataset_id' => $inside, 'datestart' => '1860', 'dateend' => '1865'] + self::BOX_ONLY);

        $straddling = Fixtures::layer(['name' => 'Straddling, mixed']);
        Fixtures::record(['dataset_id' => $straddling, 'datestart' => '1900'] + self::IN);
        Fixtures::record(['dataset_id' => $straddling, 'datestart' => '1850'] + self::OUT);
        Fixtures::record(['dataset_id' => $straddling] + self::BOX_ONLY);
        Fixtures::record(['dataset_id' => $straddling, 'datestart' => '1855']);

        $outside = Fixtures::layer(['name' => 'Outside, dated']);
        Fixtures::record(['dataset_id' => $outside, 'datestart' => '1850'] + self::OUT);
        Fixtures::record(['dataset_id' => $outside, 'dateend' => '1845'] + self::OUT);

        $undated = Fixtures::layer(['name' => 'Inside, undated']);
        Fixtures::record(['dataset_id' => $undated] + self::IN);
        Fixtures::record(['dataset_id' => $undated, 'datestart' => 'circa 1850'] + self::BOX_ONLY);

        $mixed = Fixtures::layer(['name' => 'Box only, mixed']);
        Fixtures::record(['dataset_id' => $mixed, 'datestart' => '1700'] + self::BOX_ONLY);
        Fixtures::record(['dataset_id' => $mixed, 'datestart' => '1950'] + self::OUT);

        $private = Fixtures::layer(['name' => 'Private', 'public' => false]);
        Fixtures::record(['dataset_id' => $private, 'datestart' => '1850'] + self::IN);

        // Gazetteer records, which belong to no layer.
        Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchausgaz'), 'datestart' => '1850'] + self::IN);
        Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchausgaz')] + self::BOX_ONLY);
        Fixtures::record(['datasource_id' => Fixtures::datasourceId('searchausgaz'), 'datestart' => '1850'] + self::OUT);

        $this->public = [$inside, $straddling, $outside, $undated, $mixed];
    }

    /** Follows page.next to the end, returning every page. */
    private function pages(string $url): array
    {
        $pages = [];
        while ($url !== null) {
            $r = $this->get($url);
            $r->assertStatus(200);
            $pages[] = $r;
            $this->assertLessThan(100, count($pages), 'Paging did not end.');
            $url = $r->json('page.next');
        }
        return $pages;
    }

    public function filterSets(): array
    {
        return [
            'bbox' => ['bbox=' . self::BBOX],
            'polygon' => ['polygon=' . rawurlencode(self::POLYGON)],
            'dates' => ['date_from=1840&date_to=1870'],
            'dates without undated' => ['date_from=1840&date_to=1870&include_undated=false'],
            'bbox and dates' => ['bbox=' . self::BBOX . '&date_from=1840&date_to=1870'],
            'bbox and dates without undated' => ['bbox=' . self::BBOX . '&date_from=1840&date_to=1870&include_undated=false'],
        ];
    }

    /** @dataProvider filterSets */
    public function test_catalogue_lists_exactly_the_layers_place_search_draws_on(string $qs)
    {
        $this->fixture();

        $searched = [];
        $byLayer = null;
        foreach ($this->pages('/api/v2/places?per_page=2&' . $qs) as $r) {
            foreach ($r->json('features') as $feature) {
                $layerId = $feature['properties']['layer_id'];
                if ($layerId !== null) {
                    $searched[$layerId] = ($searched[$layerId] ?? 0) + 1;
                }
            }
            $byLayer = $r->json('summary.by_layer');
        }
        ksort($searched);

        $listed = [];
        $matching = [];
        foreach ($this->pages('/api/v2/layers?per_page=2&' . $qs) as $r) {
            $this->assertSame(count($searched), $r->json('total'));
            foreach ($r->json('layers') as $layer) {
                $listed[] = $layer['id'];
                $matching[$layer['id']] = $layer['matching_count'];
            }
        }

        $this->assertSame(array_keys($searched), $listed);
        $this->assertSame($searched, $matching);
        $this->assertSame(array_keys($searched), array_column($byLayer, 'layer_id'));
        // Each filter set separates the fixture: some public layers match and some do not.
        $this->assertNotEmpty($listed);
        $this->assertLessThan(count($this->public), count($listed));
    }
}
