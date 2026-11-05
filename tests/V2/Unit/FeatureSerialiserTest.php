<?php

namespace Tests\V2\Unit;

use Tests\V2\TestCase;
use TLCMap\Api\V2\Serialise\FeatureSerialiser;

class FeatureSerialiserTest extends TestCase
{
    private const KEY_BY_ID = [1 => 'layers', 2 => 'anps', 3 => 'ncg', 4 => 'text'];

    private const ORDER = [
        'id', 'name', 'placename', 'description', 'datasource', 'layer_id', 'record_type', 'state', 'lga',
        'parish', 'feature_term', 'datestart', 'dateend', 'udatestart', 'udateend', 'source', 'external_url',
        'image_url', 'glycerine_url', 'url', 'created_at', 'updated_at', 'extended_data',
        'extended_data_unreadable',
    ];

    private function row(array $o = []): \stdClass
    {
        return (object) array_merge([
            'id' => '7', 'uid' => 't1a2b', 'title' => 'Wollombi', 'placename' => 'Wollombi',
            'description' => 'Built-in <i>text</i>', 'datasource_id' => '1', 'dataset_id' => '12',
            'record_type' => 'Town', 'state' => 'NSW', 'lga' => null, 'parish' => '', 'feature_term' => null,
            'datestart' => '1820', 'dateend' => null, 'udatestart' => '-4102444800', 'udateend' => null,
            'source' => 'A source', 'external_url' => 'http://example.org', 'image_path' => null,
            'glycerine_url' => null, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => null,
            'extended_data' => null, 'latitude' => '-32.9', 'longitude' => '151.1',
        ], $o);
    }

    private function feature(array $o = []): array
    {
        return FeatureSerialiser::feature($this->row($o), self::KEY_BY_ID);
    }

    private static function xml(string $inner): string
    {
        return '<ExtendedData>' . $inner . '</ExtendedData>';
    }

    public function testEveryPropertyInSpecOrder(): void
    {
        $f = $this->feature();
        $this->assertSame('Feature', $f['type']);
        $this->assertSame(self::ORDER, array_keys($f['properties']));
        $p = $f['properties'];
        $this->assertSame('t1a2b', $p['id']);
        $this->assertSame('Wollombi', $p['name']);
        $this->assertSame('layers', $p['datasource']);
        $this->assertSame(12, $p['layer_id']);
        $this->assertSame(-4102444800, $p['udatestart']);
        $this->assertNull($p['udateend']);
        $this->assertNull($p['lga']);
        $this->assertNull($p['parish']);
        $this->assertNull($p['updated_at']);
        $this->assertNull($p['image_url']);
        $this->assertSame(url('places/t1a2b'), $p['url']);
        $this->assertSame([151.1, -32.9], $f['geometry']['coordinates']);
        $this->assertSame('Point', $f['geometry']['type']);
    }

    public function testGazetteerRecordHasNullLayerId(): void
    {
        $p = $this->feature(['datasource_id' => 2, 'dataset_id' => null])['properties'];
        $this->assertSame('anps', $p['datasource']);
        $this->assertNull($p['layer_id']);
    }

    public function testExtendedDataIsNamespacedAndDescriptionKept(): void
    {
        $p = $this->feature(['extended_data' => self::xml(
            '<Data name="description"><value><![CDATA[contributor text]]></value></Data>'
        )])['properties'];
        $this->assertSame(['description' => 'contributor text'], $p['extended_data']);
        $this->assertSame('Built-in <i>text</i>', $p['description']);
        $this->assertFalse($p['extended_data_unreadable']);
    }

    public function testValuesArePlain(): void
    {
        $p = $this->feature(['extended_data' => self::xml(
            '<Data name="a"><value>http://x</value></Data>'
            . '<Data name="b"><value><![CDATA[<b>]]></value></Data>'
            . '<Data name="c"><value>x &amp; y</value></Data>'
            . '<Data name="latitude"><value>-1.5</value></Data>'
        )])['properties'];
        $this->assertSame(
            ['a' => 'http://x', 'b' => '<b>', 'c' => 'x & y', 'latitude' => '-1.5'],
            $p['extended_data']
        );
    }

    public function testDuplicateNamesFirstWins(): void
    {
        $p = $this->feature(['extended_data' => self::xml(
            '<Data name="k"><value>one</value></Data><Data name="k"><value>two</value></Data>'
        )])['properties'];
        $this->assertSame(['k' => 'one'], $p['extended_data']);
    }

    public function testEmptyAndMalformedExtendedData(): void
    {
        foreach ([null, '', '  ', '<ExtendedData/>'] as $stored) {
            $p = $this->feature(['extended_data' => $stored])['properties'];
            $this->assertSame('{}', json_encode($p['extended_data']));
            $this->assertFalse($p['extended_data_unreadable']);
        }
        $p = $this->feature(['extended_data' => '<ExtendedData><Data name="a"'])['properties'];
        $this->assertSame('{}', json_encode($p['extended_data']));
        $this->assertTrue($p['extended_data_unreadable']);
    }

    public function testNumericFieldNamesStayAnObject(): void
    {
        $p = $this->feature(['extended_data' => self::xml('<Data name="0"><value>z</value></Data>')])['properties'];
        $this->assertSame('{"0":"z"}', json_encode($p['extended_data']));
    }

    public function testNestedDataAndNonStandardRootAreFields(): void
    {
        $nested = '<ExtendedData><Wrap><Data name="k"><value>v</value></Data></Wrap></ExtendedData>';
        $this->assertSame(['k' => 'v'], $this->feature(['extended_data' => $nested])['properties']['extended_data']);
        $root = '<Data name="r"><value>x</value></Data>';
        $this->assertSame(['r' => 'x'], $this->feature(['extended_data' => $root])['properties']['extended_data']);
        $other = '<Other><Data name="o"><value>y</value></Data></Other>';
        $this->assertSame(['o' => 'y'], $this->feature(['extended_data' => $other])['properties']['extended_data']);
    }

    public function testDataWithoutNameIsNotAField(): void
    {
        $p = $this->feature(['extended_data' => self::xml(
            '<Data><value>a</value></Data><Data name=""><value>b</value></Data><Data name="k"><value>c</value></Data>'
        )])['properties'];
        $this->assertSame(['k' => 'c'], $p['extended_data']);
    }

    public function testCharacterReferenceCarriageReturnDecodes(): void
    {
        $p = $this->feature(['extended_data' => self::xml('<Data name="k"><value>a&#13;b</value></Data>')])['properties'];
        $this->assertSame("a\rb", $p['extended_data']['k']);
    }

    public function testParityWithSqlExtendedValue(): void
    {
        $docs = [
            self::xml('<Data name="a"><value>1</value></Data><Data name="a"><value>2</value></Data>'),
            '<ExtendedData><Wrap><Data name="k"><value><![CDATA[v]]></value></Data></Wrap></ExtendedData>',
            '<Data name="r"><value>x&#13;y &amp; z</value></Data>',
            self::xml('<Data name="b"><value>http://x</value></Data><Data><value>q</value></Data>'),
        ];
        foreach ($docs as $doc) {
            $fields = $this->feature(['extended_data' => $doc])['properties']['extended_data'];
            $this->assertNotEmpty((array) $fields);
            foreach ((array) $fields as $name => $value) {
                $sql = \DB::selectOne('select tlcmap.v2_extended_value(?, ?) as v', [$doc, (string) $name])->v;
                $this->assertSame($sql, $value, "field '$name' of $doc");
            }
        }
    }

    public function testGeometry(): void
    {
        $this->assertNull($this->feature(['latitude' => null])['geometry']);
        $this->assertNull($this->feature(['longitude' => null])['geometry']);
        $g = $this->feature(['latitude' => '0', 'longitude' => '0'])['geometry'];
        $this->assertSame(['type' => 'Point', 'coordinates' => [0.0, 0.0]], $g);
    }

    public function testImageUrlAndTimestamps(): void
    {
        $p = $this->feature(['image_path' => 'a.png', 'updated_at' => '2026-07-01 10:00:00'])['properties'];
        $this->assertSame(\Storage::disk('public')->url('images/a.png'), $p['image_url']);
        $this->assertSame('2025-12-31T13:00:00Z', $p['created_at']);
        $this->assertSame('2026-07-01T00:00:00Z', $p['updated_at']);
    }

    public function testColumnsAreRawExpressions(): void
    {
        $this->assertCount(25, FeatureSerialiser::columns());
        foreach (FeatureSerialiser::columns() as $c) {
            $this->assertInstanceOf(\Illuminate\Database\Query\Expression::class, $c);
        }
    }
}
