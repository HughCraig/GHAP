<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/** GET /api/v2/layers/{id} (spec §10.3). */
class LayerDetailTest extends TestCase
{
    private const LAYER_KEYS = [
        'id', 'name', 'description', 'subject_keywords', 'record_type', 'creator', 'publisher', 'contact',
        'citation', 'doi', 'source_url', 'linkback', 'license', 'rights', 'language', 'warning', 'declared',
        'created', 'created_at', 'updated_at', 'url',
    ];

    private function assertError($r, int $status, string $code): void
    {
        $r->assertStatus($status);
        $this->assertSame($code, $r->json('error.code'));
    }

    public function testPrivateMissingAndNonNumericLayers(): void
    {
        $private = Fixtures::layer(['public' => false]);
        $this->assertError($this->get('/api/v2/layers/' . $private), 403, 'LAYER_PRIVATE');
        $this->assertError($this->get('/api/v2/layers/1'), 404, 'LAYER_NOT_FOUND');
        $this->assertError($this->get('/api/v2/layers/abc'), 404, 'NOT_FOUND');
    }

    public function testAnyQueryParameterIsRejected(): void
    {
        $id = Fixtures::layer();
        $r = $this->get('/api/v2/layers/' . $id . '?limit=3');
        $this->assertError($r, 400, 'INVALID_PARAMETER');
        $this->assertSame('unknown', $r->json('error.details.rule'));
    }

    public function testEveryKeyIsPresentAndTextIsVerbatim(): void
    {
        $typeId = (int) DB::table('tlcmap.recordtype')->value('id');
        $type = DB::table('tlcmap.recordtype')->where('id', $typeId)->value('type');
        $id = Fixtures::layer([
            'name' => 'Music',
            'warning' => 'None',
            'license' => "  CC BY \n",
            'rights' => '',
            'creator' => 'Someone',
            'language' => 'en',
            'created' => '2021-03-01',
            'recordtype_id' => $typeId,
            'created_at' => '2021-03-01 13:11:09',
        ]);
        Fixtures::keyword($id, 'zebra');
        Fixtures::keyword($id, 'music');

        $r = $this->get('/api/v2/layers/' . $id);
        $r->assertStatus(200);
        $this->assertSame(['layer', 'summary'], array_keys($r->json()));
        $layer = $r->json('layer');
        $this->assertSame(self::LAYER_KEYS, array_keys($layer));
        $this->assertSame($id, $layer['id']);
        $this->assertSame('None', $layer['warning']);
        $this->assertSame("  CC BY \n", $layer['license']);
        $this->assertNull($layer['rights']);
        $this->assertNull($layer['publisher']);
        $this->assertSame('Someone', $layer['creator']);
        $this->assertNull($layer['description']);
        $this->assertSame(['music', 'zebra'], $layer['subject_keywords']);
        $this->assertSame($type, $layer['record_type']);
        $this->assertSame('2021-03-01', $layer['created']);
        $this->assertSame('2021-03-01T02:11:09Z', $layer['created_at']);
        $this->assertSame(url('layers/' . $id), $layer['url']);
        $this->assertSame(
            ['latitude_from', 'latitude_to', 'longitude_from', 'longitude_to', 'temporal_from', 'temporal_to'],
            array_keys($layer['declared'])
        );
    }

    public function testDeclaredIsAsStoredAndSummaryComesFromRecords(): void
    {
        $id = Fixtures::layer([
            'latitude_from' => -10, 'latitude_to' => -30, 'longitude_from' => 100.5,
            'temporal_from' => '1900',
        ]);
        Fixtures::record(['dataset_id' => $id, 'lat' => -33.0, 'lon' => 151.0]);
        Fixtures::record(['dataset_id' => $id, 'lat' => -34.0, 'lon' => 152.0]);

        $r = $this->get('/api/v2/layers/' . $id);
        $r->assertStatus(200);
        $d = $r->json('layer.declared');
        $this->assertEquals(-10, $d['latitude_from']);
        $this->assertEquals(-30, $d['latitude_to']);
        $this->assertEquals(100.5, $d['longitude_from']);
        $this->assertNull($d['longitude_to']);
        $this->assertSame('1900', $d['temporal_from']);
        $this->assertSame(2, $r->json('summary.total'));
        $this->assertEquals([151.0, -34.0, 152.0, -33.0], $r->json('summary.extent'));
        $this->assertArrayNotHasKey('by_layer', $r->json('summary'));
    }
}
