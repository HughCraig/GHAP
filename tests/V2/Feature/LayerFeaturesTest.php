<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/** GET /api/v2/layers/{id}/features (spec §10.4). */
class LayerFeaturesTest extends TestCase
{
    private function names($r): array
    {
        return array_map(function (array $f) {
            return $f['properties']['name'];
        }, $r->json('features'));
    }

    public function testErrorsAndRejectedParameters(): void
    {
        $private = Fixtures::layer(['public' => false]);
        $id = Fixtures::layer();
        $this->get('/api/v2/layers/' . $private . '/features')->assertStatus(403);
        $this->get('/api/v2/layers/1/features')->assertStatus(404);
        $this->get('/api/v2/layers/abc/features')->assertStatus(404);
        foreach (['line=time', 'bbox=1,2,3,4', 'sample=3', 'name=x'] as $qs) {
            $r = $this->get('/api/v2/layers/' . $id . '/features?' . $qs);
            $r->assertStatus(400);
            $this->assertSame('unknown', $r->json('error.details.rule'), $qs);
        }
    }

    public function testEnvelopeAndDefaultOrder(): void
    {
        $id = Fixtures::layer(['warning' => 'Check this']);
        Fixtures::record(['dataset_id' => $id, 'title' => 'C', 'dataset_order' => 2, 'lat' => -1, 'lon' => 1]);
        Fixtures::record(['dataset_id' => $id, 'title' => 'A', 'dataset_order' => 0]);
        Fixtures::record(['dataset_id' => $id, 'title' => 'B', 'dataset_order' => 1, 'lat' => -2, 'lon' => 2]);
        Fixtures::record(['dataset_id' => $id, 'title' => 'N']);
        Fixtures::record(['title' => 'Elsewhere', 'lat' => -3, 'lon' => 3]);

        $r = $this->get('/api/v2/layers/' . $id . '/features');
        $r->assertStatus(200);
        $this->assertSame(
            ['type', 'layer', 'query', 'summary', 'page', 'features'],
            array_keys($r->json())
        );
        $this->assertSame(['A', 'B', 'C', 'N'], $this->names($r));
        $this->assertSame('Check this', $r->json('layer.warning'));
        $this->assertArrayNotHasKey('by_layer', $r->json('summary'));
        $this->assertSame(4, $r->json('summary.total'));
        $this->assertSame('order', $r->json('query.sort'));
        $this->assertNull($r->json('features.0.geometry'));
        $this->assertSame(500, $r->json('page.per_page'));
    }

    public function testSortByStartWithNullsFirstAndSummaryIgnoresLimit(): void
    {
        $id = Fixtures::layer();
        Fixtures::record(['dataset_id' => $id, 'title' => 'Late', 'datestart' => '1950']);
        Fixtures::record(['dataset_id' => $id, 'title' => 'None']);
        Fixtures::record(['dataset_id' => $id, 'title' => 'Early', 'datestart' => '1850']);

        $r = $this->get('/api/v2/layers/' . $id . '/features?sort=start&nulls=first&limit=2');
        $r->assertStatus(200);
        $this->assertSame(['None', 'Early'], $this->names($r));
        $this->assertSame(3, $r->json('summary.total'));
        $this->assertNull($r->json('page.next'));
    }

    public function testPagingVisitsEveryRecordOnceWithTheLayerOnEveryPage(): void
    {
        $id = Fixtures::layer(['warning' => 'Beware']);
        $rows = [];
        for ($n = 0; $n < 1203; $n++) {
            $rows[] = [
                'id' => 2000000 + $n, 'uid' => 't' . dechex(2000000 + $n), 'title' => 'R' . $n,
                'datasource_id' => 1, 'dataset_id' => $id,
                // Ties and gaps in dataset_order exercise the id tiebreak and the null placement.
                'dataset_order' => $n % 7 === 0 ? null : intdiv($n, 3),
                'created_at' => Fixtures::TIMESTAMP, 'updated_at' => Fixtures::TIMESTAMP,
            ];
        }
        foreach (array_chunk($rows, 300) as $chunk) {
            DB::table('tlcmap.dataitem')->insert($chunk);
        }

        $seen = [];
        $layers = [];
        $r = $this->get('/api/v2/layers/' . $id . '/features?per_page=500');
        for ($i = 0; $i < 10; $i++) {
            $r->assertStatus(200);
            $layers[] = $r->json('layer');
            foreach ($this->names($r) as $name) {
                $seen[] = $name;
            }
            if ($r->json('page.next') === null) {
                break;
            }
            $r = $this->get($r->json('page.next'));
        }
        $this->assertCount(3, $layers);
        $this->assertSame($layers[0], $layers[2]);
        $this->assertSame('Beware', $layers[1]['warning']);
        $this->assertCount(1203, $seen);
        $this->assertCount(1203, array_unique($seen));
    }
}
