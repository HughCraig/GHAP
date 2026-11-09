<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;

/**
 * A2 changes to place search (spec §4): the `parish` filter, and vocabulary filters that ignore
 * whitespace around the stored value.
 */
class VocabularyFilterChangesTest extends TestCase
{
    private function search(string $qs)
    {
        return $this->get('/api/v2/places?' . $qs);
    }

    /** The record ids a search returns, sorted. */
    private function ids(string $qs): array
    {
        $r = $this->search($qs);
        $r->assertStatus(200);
        $ids = [];
        foreach ($r->json('features') as $f) {
            $ids[] = (int) DB::table('tlcmap.dataitem')->where('uid', $f['properties']['id'])->value('id');
        }
        sort($ids);

        return $ids;
    }

    public function test_parish_is_whole_value_and_case_insensitive()
    {
        $a = Fixtures::record(['parish' => 'WOLLOMBI']);
        $b = Fixtures::record(['parish' => 'Wollombi']);
        Fixtures::record(['parish' => 'Wollombi North']);

        $r = $this->search('parish=wollombi');
        $r->assertStatus(200);
        $this->assertSame(2, $r->json('summary.total'));
        $this->assertSame([$a, $b], $this->ids('parish=wollombi'));
        $this->assertSame('wollombi', $r->json('query.parish'));

        $this->assertSame(0, $this->search('parish=Wollomb')->json('summary.total'));
    }

    public function test_padded_stored_values_match_for_each_vocabulary_field()
    {
        foreach (['state', 'lga', 'parish', 'feature_term'] as $field) {
            $padded = [
                Fixtures::record([$field => ' hill']),
                Fixtures::record([$field => 'hill ']),
                Fixtures::record([$field => "hill\t"]),
                Fixtures::record([$field => 'hill']),
            ];
            $short = Fixtures::record([$field => 'hil']);

            $this->assertSame($padded, $this->ids($field . '=hill'), $field);
            $this->assertSame([$short], $this->ids($field . '=hil'), $field);

            DB::table('tlcmap.dataitem')->whereIn('id', array_merge($padded, [$short]))->delete();
        }

        $typeId = DB::table('tlcmap.recordtype')->insertGetId(['type' => ' Site ']);
        $rec = Fixtures::record(['recordtype_id' => $typeId]);
        $this->assertSame([$rec], $this->ids('record_type=site'));
    }

    public function test_padded_records_count_in_the_summary_and_by_layer()
    {
        $layer = Fixtures::layer();
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => ' hill', 'lat' => -32.7, 'lon' => 151.2]);
        Fixtures::record(['dataset_id' => $layer, 'feature_term' => 'hill', 'lat' => -32.7, 'lon' => 151.2]);

        $r = $this->search('feature_term=hill&layers=' . $layer);
        $this->assertSame(2, $r->json('summary.total'));
        $this->assertSame(2, $r->json('summary.by_layer.0.count'));
    }
}
