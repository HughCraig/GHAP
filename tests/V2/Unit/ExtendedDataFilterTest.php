<?php

namespace Tests\V2\Unit;

use Tests\V2\Support\Civil;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\ExtendedDataFilter;

class ExtendedDataFilterTest extends TestCase
{
    private function one(string $expr): array
    {
        $clauses = ExtendedDataFilter::parse($expr, 10)->clauses();
        $this->assertCount(1, $clauses);
        return $clauses[0];
    }

    public function sameClause(): array
    {
        return [['Years>50'], ['Years > 50'], ['"Years" > "50"'], ["'Years'>'50'"]];
    }

    /** @dataProvider sameClause */
    public function testEquivalentForms(string $expr): void
    {
        $c = $this->one($expr);
        $this->assertSame(['field' => 'Years', 'op' => '>', 'value' => '50', 'date' => null], $c);
    }

    public function testFieldWithSpaces(): void
    {
        $this->assertSame('Catalogue no', $this->one('Catalogue no = 3')['field']);
        $this->assertSame('Sandy', $this->one('Sandy textmatch sand')['field']);
    }

    public function testTwoClausesAndCaseInsensitiveOperator(): void
    {
        foreach (['textmatch', 'TEXTMATCH', 'TextMatch'] as $op) {
            $cl = ExtendedDataFilter::parse("Name $op bridge AND Years < 10", 10)->clauses();
            $this->assertCount(2, $cl);
            $this->assertSame('textmatch', $cl[0]['op']);
            $this->assertSame('bridge', $cl[0]['value']);
            $this->assertSame(['Years', '<', '10'], [$cl[1]['field'], $cl[1]['op'], $cl[1]['value']]);
        }
    }

    public function testQuotedValueKeepsAnd(): void
    {
        $this->assertSame('Bread AND Butter', $this->one('Title = "Bread AND Butter"')['value']);
    }

    public function testDateOperand(): void
    {
        $c = $this->one('Built after 1850-03');
        $this->assertSame('after', $c['op']);
        $this->assertSame(Civil::udate(1850, 3), $c['date']->start);
    }

    public function testNegativeDecimal(): void
    {
        $this->assertSame('-2.5', $this->one('Years > -2.5')['value']);
    }

    public function testToEchoIsAsGiven(): void
    {
        $this->assertSame('  Years>50 ', ExtendedDataFilter::parse('  Years>50 ', 10)->toEcho());
    }

    public function rejections(): array
    {
        return [
            ['Years', 5, 'an operator'],
            ['= 5', 0, 'a field name'],
            ['Years >', 7, 'a value'],
            ['Years > many', 8, 'a number'],
            ['Built before soon', 13, 'a date'],
            ['Years > 50 AND', 14, 'a clause'],
            ['"Years > 50', 0, 'a closing quote'],
            ['Years > 50 and Size < 3', 8, 'a number'],
            ['Años > x', 7, 'a number'],
        ];
    }

    /** @dataProvider rejections */
    public function testRejections(string $expr, int $position, string $expected): void
    {
        try {
            ExtendedDataFilter::parse($expr, 10);
        } catch (ApiException $e) {
            $this->assertSame(400, $e->status());
            $this->assertSame('FILTER_REJECTED', $e->errorCode());
            $d = $e->details();
            $this->assertSame($expr, $d['expression']);
            $this->assertSame($position, $d['position']);
            $this->assertSame($expected, $d['expected']);
            return;
        }
        $this->fail('Expected ApiException');
    }

    public function testElevenClauses(): void
    {
        $parts = [];
        for ($i = 1; $i <= 11; $i++) {
            $parts[] = "F$i = $i";
        }
        $first10 = implode(' AND ', array_slice($parts, 0, 10));
        $this->assertCount(10, ExtendedDataFilter::parse($first10, 10)->clauses());
        $this->testRejections(implode(' AND ', $parts), strlen($first10) + 5, 'at most 10 clauses');
    }
}
