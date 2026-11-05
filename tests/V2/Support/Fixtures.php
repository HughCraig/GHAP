<?php

namespace Tests\V2\Support;

use Illuminate\Support\Facades\DB;

/**
 * Inserts rows into the test database. Ids come from a counter, passed explicitly on insert.
 */
class Fixtures
{
    const FIRST_ID = 900001;
    const TIMESTAMP = '2026-01-01 00:00:00';

    private static $next = self::FIRST_ID;
    private static $count = ['layer' => 0, 'record' => 0];

    public static function reset(): void
    {
        self::$next = self::FIRST_ID;
        self::$count = ['layer' => 0, 'record' => 0];
    }

    private static function nextId(): int
    {
        return self::$next++;
    }

    public static function layer(array $attrs = []): int
    {
        $id = self::nextId();
        $n = ++self::$count['layer'];

        $row = array_merge([
            'id' => $id,
            'name' => 'Layer ' . $n,
            'description' => '',
            'public' => true,
            'created_at' => self::TIMESTAMP,
            'updated_at' => self::TIMESTAMP,
        ], $attrs);

        DB::table('tlcmap.dataset')->insert($row);

        return $id;
    }

    public static function record(array $attrs = []): int
    {
        $id = self::nextId();
        $n = ++self::$count['record'];

        if (array_key_exists('lat', $attrs)) {
            $attrs['latitude'] = $attrs['lat'];
            unset($attrs['lat']);
        }
        if (array_key_exists('lon', $attrs)) {
            $attrs['longitude'] = $attrs['lon'];
            unset($attrs['lon']);
        }

        $datasourceId = isset($attrs['datasource_id'])
            ? $attrs['datasource_id']
            : (isset($attrs['dataset_id']) ? 1 : 2);

        $prefixes = [1 => 't', 4 => 't', 2 => 'a', 3 => 'n'];

        $row = array_merge([
            'id' => $id,
            'title' => 'Place ' . $n,
            'datasource_id' => $datasourceId,
            'uid' => (isset($prefixes[$datasourceId]) ? $prefixes[$datasourceId] : '') . dechex($id),
            'created_at' => self::TIMESTAMP,
            'updated_at' => self::TIMESTAMP,
        ], $attrs);

        DB::table('tlcmap.dataitem')->insert($row);

        return $id;
    }

    public static function keyword(int $layerId, string $keyword): void
    {
        $existing = DB::table('tlcmap.subject_keyword')->where('keyword', $keyword)->value('id');
        $keywordId = $existing ?: DB::table('tlcmap.subject_keyword')->insertGetId(['keyword' => $keyword]);

        DB::table('tlcmap.dataset_subject_keyword')->insert([
            'dataset_id' => $layerId,
            'subject_keyword_id' => $keywordId,
        ]);
    }

    public static function datasourceId(string $searchParamName): int
    {
        return (int) DB::table('tlcmap.datasource')->where('search_param_name', $searchParamName)->value('id');
    }
}
