<?php

namespace Tests\V2\Unit;

use Illuminate\Http\Request;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\VocabularyField;

class VocabularyFieldTest extends TestCase
{
    private function set(string $qs): ParameterSet
    {
        return ParameterSet::fromRequest(Request::create('/api/v2/vocabulary?' . $qs), ['field']);
    }

    public function testEachNameParsesToItsColumn(): void
    {
        $columns = [
            'feature_term' => 'i.feature_term', 'state' => 'i.state', 'lga' => 'i.lga',
            'parish' => 'i.parish', 'record_type' => 'rt.type',
        ];
        $this->assertSame(array_keys($columns), VocabularyField::FIELDS);
        foreach ($columns as $name => $column) {
            $field = VocabularyField::parse($this->set('field=' . $name));
            $this->assertSame($name, $field->name);
            $this->assertSame($column, $field->column);
        }
    }

    public function testNamedGivesTheColumn(): void
    {
        $this->assertSame('i.feature_term', VocabularyField::named('feature_term')->column);
    }

    public function testKeyAndHasValueUseTheWhitespaceSet(): void
    {
        $field = VocabularyField::named('state');
        $this->assertSame("lower(btrim(i.state, E' \\t\\r\\n'))", $field->key());
        $this->assertStringContainsString("btrim(i.state, E' \\t\\r\\n')", $field->hasValue());
    }

    public function testMissingFieldIsRequired(): void
    {
        $this->assertRejected('', 'required');
    }

    public function testUnknownFieldIsOneOf(): void
    {
        $e = $this->assertRejected('field=parishes', 'one_of');
        $this->assertSame('feature_term, state, lga, parish, record_type', $e->details()['accepts']);
    }

    private function assertRejected(string $qs, string $rule): ApiException
    {
        try {
            VocabularyField::parse($this->set($qs));
        } catch (ApiException $e) {
            $this->assertSame('INVALID_PARAMETER', $e->errorCode());
            $this->assertSame('field', $e->details()['parameter']);
            $this->assertSame($rule, $e->details()['rule']);
            return $e;
        }
        $this->fail('Expected ApiException');
    }
}
