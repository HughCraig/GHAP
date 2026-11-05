<?php

namespace Tests\V2\Unit;

use Illuminate\Http\Request;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\ParameterSet;

class ParameterSetTest extends TestCase
{
    private const ALLOWED = ['name', 'sources', 'layers', 'bbox', 'per_page', 'limit', 'flag', 'match', 'a_b'];

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

    public function testUnknownParameter()
    {
        $this->assertRejected(function () { $this->set('date_form=x'); }, 'date_form', 'unknown', implode(', ', self::ALLOWED));
    }

    public function testRepeatedParameters()
    {
        $this->assertRejected(function () { $this->set('sources=anps&sources=ncg'); }, 'sources', 'repeated');
        $this->assertRejected(function () { $this->set('layers[]=1'); }, 'layers', 'repeated');
        $this->assertRejected(function () { $this->set('layers[]=1&layers[]=2'); }, 'layers', 'repeated');
    }

    public function testEmptyParameter()
    {
        $this->assertRejected(function () { $this->set('bbox='); }, 'bbox', 'empty');
        $this->assertRejected(function () { $this->set('bbox'); }, 'bbox', 'empty');
    }

    public function testStringDecoding()
    {
        $this->assertSame('Mount Vincent', $this->set('name=Mount+Vincent')->string('name'));
        $this->assertSame('Mount Vincent', $this->set('name=Mount%20Vincent')->string('name'));
        $this->assertSame('Ngun’gara', $this->set('name=Ngun%E2%80%99gara')->string('name'));
        $this->assertSame('a&b', $this->set('name=a%26b')->string('name'));
        $this->assertNull($this->set('')->string('name'));
        $this->assertTrue($this->set('name=x')->has('name'));
        $this->assertFalse($this->set('name=x')->has('limit'));
        $this->assertSame('x', $this->set('name=x')->raw('name'));
        $this->assertSame(['name' => 'x', 'limit' => '5'], $this->set('name=x&limit=5')->all());
    }

    public function testNamesWithDotsAreNormalisedLikePhp()
    {
        $this->assertSame('1', $this->set('a.b=1')->raw('a_b'));
        $this->assertRejected(function () { $this->set('a.b=1&a_b=2'); }, 'a_b', 'repeated');
    }

    public function testStringMaxLength()
    {
        $this->assertRejected(function () { $this->set('name=' . str_repeat('a', 201))->string('name'); }, 'name', 'max_length', 'at most 200 characters');
        $this->assertSame(200, mb_strlen($this->set('name=' . str_repeat('%E2%80%99', 200))->string('name')));
        $this->assertRejected(function () { $this->set('name=abcdef')->string('name', 5); }, 'name', 'max_length', 'at most 5 characters');
    }

    public function testBool()
    {
        $this->assertTrue($this->set('flag=true')->bool('flag', false));
        $this->assertFalse($this->set('flag=false')->bool('flag', true));
        $this->assertTrue($this->set('')->bool('flag', true));
        foreach (['1', 'yes', 'TRUE'] as $v) {
            $this->assertRejected(function () use ($v) { $this->set('flag=' . $v)->bool('flag', false); }, 'flag', 'boolean', 'true, false');
        }
    }

    public function testInt()
    {
        $this->assertSame(25, $this->set('per_page=25')->int('per_page', 1, 5000, 500));
        $this->assertSame(500, $this->set('')->int('per_page', 1, 5000, 500));
        $this->assertNull($this->set('')->int('per_page', 1, 5000, null));
        $this->assertSame(-3, $this->set('limit=-3')->int('limit', -10, 10, null));
        $this->assertRejected(function () { $this->set('per_page=99999999999999999999')->int('per_page', 1, 5000, 500); }, 'per_page', 'range', '1–5000');
        $this->assertRejected(function () { $this->set('per_page=-99999999999999999999')->int('per_page', 1, 5000, 500); }, 'per_page', 'range', '1–5000');
        $this->assertRejected(function () { $this->set('per_page=0')->int('per_page', 1, 5000, 500); }, 'per_page', 'range', '1–5000');
        $this->assertRejected(function () { $this->set('limit=-1')->int('limit', 0, 10, null); }, 'limit', 'range', '0–10');
        foreach (['1.5', '+3', '1e3', 'abc', '--1', '-'] as $v) {
            $this->assertRejected(function () use ($v) { $this->set('limit=' . rawurlencode($v))->int('limit', 0, 10, null); }, 'limit', 'integer');
        }
    }

    public function testEnum()
    {
        $values = ['exact', 'contains', 'fuzzy'];
        $this->assertSame('fuzzy', $this->set('match=fuzzy')->enum('match', $values, 'exact'));
        $this->assertSame('exact', $this->set('')->enum('match', $values, 'exact'));
        $this->assertNull($this->set('')->enum('match', $values, null));
        $this->assertRejected(function () use ($values) { $this->set('match=Fuzzy')->enum('match', $values, 'exact'); }, 'match', 'one_of', 'exact, contains, fuzzy');
    }
}
