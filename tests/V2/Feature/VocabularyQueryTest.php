<?php

namespace Tests\V2\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\VocabularyField;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\Vocabulary;
use TLCMap\Api\V2\Serialise\VocabularySerialiser;

class VocabularyQueryTest extends TestCase
{
    private function terms(int $layer, array $values): void
    {
        foreach ($values as $value) {
            Fixtures::record(['dataset_id' => $layer, 'feature_term' => $value]);
        }
    }

    private function entries(RecordFilters $f, string $field): array
    {
        return array_map(
            [VocabularySerialiser::class, 'entry'],
            Vocabulary::all($f, VocabularyField::named($field))
        );
    }

    public function testCaseVariantsAndPaddingFormOneEntry()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, ['Bay', 'Bay', 'Bay', 'bay', ' Bay']);

        $this->assertSame([[
            'value' => 'Bay',
            'record_count' => 5,
            'spellings' => [
                ['value' => 'Bay', 'record_count' => 3],
                ['value' => ' Bay', 'record_count' => 1],
                ['value' => 'bay', 'record_count' => 1],
            ],
        ]], $this->entries(RecordFilters::forLayer($layer), 'feature_term'));
    }

    public function testValueIsTheMostFrequentSpellingTrimmedWithTiesUnderC()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, [' hill', ' hill', 'Hill', 'Hill']);

        $entries = $this->entries(RecordFilters::forLayer($layer), 'feature_term');
        $this->assertCount(1, $entries);
        $this->assertSame('hill', $entries[0]['value']);
        $this->assertSame([' hill', 'Hill'], array_column($entries[0]['spellings'], 'value'));
    }

    public function testBlankInEveryFormIsNoValue()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, [null, '', '   ', "\t", "\r\n", 'Bay']);
        $f = RecordFilters::forLayer($layer);
        $field = VocabularyField::named('feature_term');

        $this->assertSame(
            ['total' => 6, 'with_value' => 1, 'without_value' => 5, 'keys' => 1],
            Vocabulary::records($f, $field)
        );
        $this->assertSame(['Bay'], array_column($this->entries($f, 'feature_term'), 'value'));
    }

    public function testRecordTypeReadsTheRecordTypeTable()
    {
        $layer = Fixtures::layer();
        $site = DB::table('tlcmap.recordtype')->insertGetId(['type' => 'Site']);
        $placename = DB::table('tlcmap.recordtype')->insertGetId(['type' => 'Placename']);
        Fixtures::record(['dataset_id' => $layer, 'recordtype_id' => $site]);
        Fixtures::record(['dataset_id' => $layer, 'recordtype_id' => $placename]);
        Fixtures::record(['dataset_id' => $layer, 'recordtype_id' => null]);

        $f = RecordFilters::forLayer($layer);
        $this->assertSame(['Placename', 'Site'], array_column($this->entries($f, 'record_type'), 'value'));
        $this->assertSame(
            ['total' => 3, 'with_value' => 2, 'without_value' => 1, 'keys' => 2],
            Vocabulary::records($f, VocabularyField::named('record_type'))
        );
    }

    public function testFiltersNarrowTheGroups()
    {
        $layer = Fixtures::layer();
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => 'in', 'lon' => 150.5, 'lat' => -32.5]);
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => 'In', 'lon' => 150.6, 'lat' => -32.6]);
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => 'out', 'lon' => 160.5, 'lat' => -32.5]);

        $request = Request::create('/api/v2/test?' . http_build_query(['layers' => $layer, 'bbox' => '150,-33,151,-32']));
        $f = RecordFilters::fromParams(ParameterSet::fromRequest($request, ['layers', 'bbox']));

        $entries = $this->entries($f, 'feature_term');
        $this->assertSame(['In'], array_column($entries, 'value'));
        $this->assertSame([2], array_column($entries, 'record_count'));
        $this->assertSame(2, Vocabulary::records($f, VocabularyField::named('feature_term'))['total']);
    }

    public function testAllOrdersByCountThenKey()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, ['a', 'b', 'b', 'b', 'c', 'c', 'c']);

        $this->assertSame(
            ['b', 'c', 'a'],
            array_column($this->entries(RecordFilters::forLayer($layer), 'feature_term'), 'value')
        );
    }

    public function testGroupedExposesExactlyTheColumnsAndNoOrderOrLimit()
    {
        $layer = Fixtures::layer();
        $this->terms($layer, ['a', 'a']);

        $q = Vocabulary::grouped(RecordFilters::forLayer($layer), VocabularyField::named('feature_term'));
        $this->assertEmpty($q->orders);
        $this->assertNull($q->limit);
        $row = $q->get()->first();
        $this->assertSame(['key', 'record_count', 'count_key', 'spellings'], array_keys((array) $row));
        $this->assertSame('0000000000000000002 a', $row->count_key);
    }
}
