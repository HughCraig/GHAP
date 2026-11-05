<?php

namespace Tests\V2\Unit;

use Tests\V2\Support\Civil;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\Bbox;
use TLCMap\Api\V2\Params\DateBound;
use TLCMap\Api\V2\Params\LayerIds;
use TLCMap\Api\V2\Params\Polygon;
use TLCMap\Api\V2\Params\Sources;

class ValueParsersTest extends TestCase
{
    private function assertRejected(callable $fn, string $parameter): void
    {
        try {
            $fn();
        } catch (ApiException $e) {
            $this->assertSame(400, $e->status());
            $this->assertSame('INVALID_PARAMETER', $e->errorCode());
            $this->assertSame($parameter, $e->details()['parameter']);
            return;
        }
        $this->fail('Expected ApiException');
    }

    public function testBboxParses(): void
    {
        $b = Bbox::parse('150.8,-33.1,151.4,-32.6');
        $this->assertSame([150.8, -33.1, 151.4, -32.6], $b->toArray());
        $this->assertFalse($b->crossesAntimeridian());
        $this->assertSame(150.8, $b->minLon);
        $this->assertTrue(Bbox::parse('170,-10,-170,10')->crossesAntimeridian());
    }

    public function badBboxes(): array
    {
        return [['150,-33,151'], ['a,1,2,3'], ['1e400,0,1,1'], ['NaN,0,1,1'], ['INF,0,1,1'],
            ['181,0,182,1'], ['0,-91,1,0'], ['0,5,1,4']];
    }

    /** @dataProvider badBboxes */
    public function testBboxRejects(string $v): void
    {
        $this->assertRejected(function () use ($v) {
            Bbox::parse($v);
        }, 'bbox');
    }

    public function testPolygon(): void
    {
        $p = Polygon::parse('151 -33,151.5 -33,151.5 -32.5', 1000);
        $this->assertCount(4, $p->vertices());
        $this->assertSame($p->vertices()[0], $p->vertices()[3]);
        $this->assertCount(3, $p->toArray());
        $closed = Polygon::parse('151 -33, 151.5 -33,151.5 -32.5,151 -33', 1000);
        $this->assertCount(4, $closed->vertices());
    }

    public function badPolygons(): array
    {
        $many = implode(',', array_map(function ($i) {
            return ($i / 10) . ' 1';
        }, range(1, 1001)));
        return [['1 1,2 2'], ['1 1,2 2,3'], [$many], ['200 1,2 2,3 3'], ['1 1,2 2,1 1'], ['1 1,x 2,3 3']];
    }

    /** @dataProvider badPolygons */
    public function testPolygonRejects(string $v): void
    {
        $this->assertRejected(function () use ($v) {
            Polygon::parse($v, 1000);
        }, 'polygon');
    }

    public function testDateBound(): void
    {
        $d = DateBound::parse('date_from', '1820');
        $this->assertSame('1820', $d->text);
        $this->assertSame(Civil::udate(1820), $d->start);
        $this->assertSame(Civil::udate(1821) - 1, $d->end);
    }

    public function testDateBoundRejects(): void
    {
        $this->assertRejected(function () {
            DateBound::parse('date_from', 'c.1820');
        }, 'date_from');
        $this->assertRejected(function () {
            DateBound::parse('date_to', str_repeat('9', 30));
        }, 'date_to');
    }

    public function testSources(): void
    {
        $this->assertSame(['anps', 'ncg'], Sources::parse('anps,ncg,anps'));
        $this->assertSame(['layers', 'ncg'], Sources::parse('ncg,layers'));
        foreach (['gazetteer', ','] as $bad) {
            $this->assertRejected(function () use ($bad) {
                Sources::parse($bad);
            }, 'sources');
        }
        $this->assertSame(['layers' => 1, 'anps' => 2, 'ncg' => 3, 'text' => 4], Sources::idsByKey());
        $this->assertSame([1 => 'layers', 2 => 'anps', 3 => 'ncg', 4 => 'text'], Sources::keyById());
    }

    public function testLayerIds(): void
    {
        $this->assertSame([5, 3], LayerIds::parse('5,3,5', 100));
        foreach (['0', '-1', 'abc', '1.5', implode(',', range(1, 101))] as $bad) {
            $this->assertRejected(function () use ($bad) {
                LayerIds::parse($bad, 100);
            }, 'layers');
        }
    }
}
