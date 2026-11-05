<?php

namespace Tests\V2\Sql;

use Illuminate\Support\Facades\DB;
use Tests\V2\TestCase;

class ExtendedValueFunctionTest extends TestCase
{
    private function value($xml, $field)
    {
        return DB::selectOne('select tlcmap.v2_extended_value(?, ?) as v', [$xml, $field])->v;
    }

    private function data($name, $value)
    {
        return '<Data name="' . $name . '"><value><![CDATA[' . $value . ']]></value></Data>';
    }

    public function testReturnsTheValueOfANamedField()
    {
        $xml = '<ExtendedData>' . $this->data('Years', '52') . '</ExtendedData>';
        $this->assertSame('52', $this->value($xml, 'Years'));
    }

    public function testFieldNamesAreCaseSensitive()
    {
        $xml = '<ExtendedData>' . $this->data('Years', '52') . '</ExtendedData>';
        $this->assertNull($this->value($xml, 'years'));
    }

    public function testFieldNameWithQuotesIsJustText()
    {
        $xml = '<ExtendedData><Data name="a&quot;b\'c"><value>ok</value></Data></ExtendedData>';
        $this->assertSame('ok', $this->value($xml, 'a"b\'c'));
    }

    public function testFirstDuplicateWins()
    {
        $xml = '<ExtendedData>' . $this->data('A', '1') . $this->data('A', '2') . '</ExtendedData>';
        $this->assertSame('1', $this->value($xml, 'A'));
    }

    public function testEntitiesAreDecoded()
    {
        $xml = '<ExtendedData><Data name="A"><value>Fish &amp; Chips</value></Data></ExtendedData>';
        $this->assertSame('Fish & Chips', $this->value($xml, 'A'));
    }

    public function nullProvider()
    {
        return [
            'absent field' => ['<ExtendedData><Data name="A"><value>1</value></Data></ExtendedData>', 'B'],
            'null text' => [null, 'A'],
            'empty' => ['', 'A'],
            'blank' => ['   ', 'A'],
            'empty element' => ['<ExtendedData/>', 'A'],
            'malformed' => ['<ExtendedData><Data name="A">', 'A'],
            'null field' => ['<ExtendedData><Data name="A"><value>1</value></Data></ExtendedData>', null],
        ];
    }

    /**
     * @dataProvider nullProvider
     */
    public function testReturnsNull($xml, $field)
    {
        $this->assertNull($this->value($xml, $field));
    }
}
