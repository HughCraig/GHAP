<?php

namespace Tests\V2\Unit;

use Illuminate\Http\Request;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Sample;
use TLCMap\Api\V2\Params\Sort;

class PagingParamsTest extends TestCase
{
    private const ALLOWED = ['sort', 'direction', 'nulls', 'per_page', 'limit', 'cursor', 'sample', 'seed'];

    private function set(string $qs): ParameterSet
    {
        return ParameterSet::fromRequest(Request::create('/api/v2/places?' . $qs), self::ALLOWED);
    }

    private function assertRejected(callable $fn, string $parameter, string $rule, ?string $accepts = null): void
    {
        try {
            $fn();
        } catch (ApiException $e) {
            $this->assertSame(400, $e->status());
            $this->assertSame('INVALID_PARAMETER', $e->errorCode());
            $this->assertSame($parameter, $e->details()['parameter']);
            $this->assertSame($rule, $e->details()['rule']);
            if ($accepts !== null) {
                $this->assertSame($accepts, $e->details()['accepts']);
            }
            return;
        }
        $this->fail('Expected ApiException');
    }

    public function testSortDefaults(): void
    {
        $s = Sort::parse($this->set(''), ['id', 'title'], 'id');
        $this->assertSame('id', $s->key);
        $this->assertSame('asc', $s->direction);
        $this->assertSame('last', $s->nulls);
        $this->assertSame(['sort' => 'id', 'direction' => 'asc', 'nulls' => 'last'], $s->toEcho());
    }

    public function testSortGiven(): void
    {
        $s = Sort::parse($this->set('sort=title&direction=desc&nulls=first'), ['id', 'title'], 'id');
        $this->assertSame(['sort' => 'title', 'direction' => 'desc', 'nulls' => 'first'], $s->toEcho());
    }

    public function testSortRejects(): void
    {
        $this->assertRejected(function () { Sort::parse($this->set('sort=name'), ['id', 'title'], 'id'); }, 'sort', 'one_of', 'id, title');
        $this->assertRejected(function () { Sort::parse($this->set('direction=up'), ['id'], 'id'); }, 'direction', 'one_of', 'asc, desc');
        $this->assertRejected(function () { Sort::parse($this->set('nulls=middle'), ['id'], 'id'); }, 'nulls', 'one_of', 'first, last');
        $this->assertRejected(function () { Sort::parse($this->set('direction=ASC'), ['id'], 'id'); }, 'direction', 'one_of');
    }

    public function testPagingDefaults(): void
    {
        $p = Paging::parse($this->set(''), 500, 5000);
        $this->assertSame(500, $p->perPage);
        $this->assertNull($p->cursor);
        $this->assertNull($p->limit);
    }

    public function testPagingGiven(): void
    {
        $p = Paging::parse($this->set('per_page=5000&limit=1200&cursor=abc_-1'), 500, 5000);
        $this->assertSame(5000, $p->perPage);
        $this->assertSame(1200, $p->limit);
        $this->assertSame('abc_-1', $p->cursor);

        $huge = Paging::parse($this->set('limit=999999999999999999'), 500, 5000);
        $this->assertSame(999999999999999999, $huge->limit);
    }

    public function testPagingRejects(): void
    {
        $this->assertRejected(function () { Paging::parse($this->set('per_page=5001'), 500, 5000); }, 'per_page', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('per_page=1001'), 100, 1000); }, 'per_page', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('per_page=0'), 500, 5000); }, 'per_page', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('per_page=x'), 500, 5000); }, 'per_page', 'integer');
        $this->assertRejected(function () { Paging::parse($this->set('limit=0'), 500, 5000); }, 'limit', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('limit=-3'), 500, 5000); }, 'limit', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('limit=99999999999999999999999'), 500, 5000); }, 'limit', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('limit=' . PHP_INT_MAX . '0'), 500, 5000); }, 'limit', 'range');
        $this->assertRejected(function () { Paging::parse($this->set('limit=1.5'), 500, 5000); }, 'limit', 'integer');
    }

    public function testSampleAbsent(): void
    {
        $this->assertNull(Sample::parse($this->set('sort=title'), 5000));
    }

    public function testSampleWithSeed(): void
    {
        $s = Sample::parse($this->set('sample=25&seed=abc'), 5000);
        $this->assertSame(25, $s->size);
        $this->assertSame('abc', $s->seed);
    }

    public function testSampleGeneratesASeed(): void
    {
        $a = Sample::parse($this->set('sample=5000'), 5000);
        $b = Sample::parse($this->set('sample=1'), 5000);
        $this->assertSame(5000, $a->size);
        $this->assertRegExp('/^[0-9a-f]{16}$/D', $a->seed);
        $this->assertNotSame($a->seed, $b->seed);
    }

    public function testSampleRejects(): void
    {
        $this->assertRejected(function () { Sample::parse($this->set('sample=5001'), 5000); }, 'sample', 'range');
        $this->assertRejected(function () { Sample::parse($this->set('sample=0'), 5000); }, 'sample', 'range');
        $this->assertRejected(function () { Sample::parse($this->set('sample=1&seed=' . str_repeat('s', 65)), 5000); }, 'seed', 'max_length');
        $this->assertSame(64, strlen(Sample::parse($this->set('sample=1&seed=' . str_repeat('s', 64)), 5000)->seed));
        $this->assertRejected(function () { Sample::parse($this->set('seed=abc'), 5000); }, 'seed', 'requires', 'sample');

        $accepts = 'not combined with sort, direction, nulls, limit or cursor';
        foreach (['sort=title', 'direction=asc', 'nulls=last', 'limit=3', 'cursor=abc'] as $other) {
            $this->assertRejected(function () use ($other) { Sample::parse($this->set("sample=3&$other"), 5000); }, 'sample', 'conflicts', $accepts);
        }
    }

    public function testNextUrlIsNullWithoutACursor(): void
    {
        $this->assertNull(Paging::nextUrl(Request::create('/api/v2/places?per_page=2'), null));
    }

    public function testNextUrlAddsTheCursor(): void
    {
        $request = Request::create('/api/v2/places?name=Two%20words&sources=anps,ncg&per_page=2');
        $this->assertSame(
            'http://localhost/api/v2/places?name=Two%20words&sources=anps%2Cncg&per_page=2&cursor=abc_-9',
            Paging::nextUrl($request, 'abc_-9')
        );
    }

    public function testNextUrlReplacesTheCursorInPlace(): void
    {
        $request = Request::create('/api/v2/places?cursor=old&per_page=2');
        $this->assertSame(
            'http://localhost/api/v2/places?cursor=new&per_page=2',
            Paging::nextUrl($request, 'new')
        );
    }

    public function testNextUrlWithoutOtherParameters(): void
    {
        $this->assertSame(
            'http://localhost/api/v2/layers/7/features?cursor=xyz',
            Paging::nextUrl(Request::create('/api/v2/layers/7/features'), 'xyz')
        );
    }
}
