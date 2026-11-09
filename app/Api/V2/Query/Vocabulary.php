<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\Sort;
use TLCMap\Api\V2\Params\VocabularyField;

/**
 * How a field's values are grouped (A2 spec §5.3–5.4). The single definition, used by the vocabulary
 * endpoint and by a layer's feature terms, so the two cannot group differently.
 */
class Vocabulary
{
    /** Sort name => [key expression, cursor key type] over `grouped` as g; trusted SQL, no bindings. */
    public const SORT_KEYS = [
        'value' => ['g.key COLLATE "C"', 'string'],
        'record_count' => ['g.count_key COLLATE "C"', 'string'],
    ];

    public const DEFAULT_SORT = 'value';
    /**
     * One row per key among the matching records that have a value: `key`, `record_count`,
     * `count_key` (the count, zero-padded to 19 digits, then the key: under COLLATE "C" it orders
     * as record_count then key, as one unique string) and `spellings` (json, A2 spec §5.3's order).
     * No ORDER BY or LIMIT; the caller orders and pages.
     */
    public static function grouped(RecordFilters $f, VocabularyField $field): Builder
    {
        $spellings = RecordQuery::build($f)
            ->whereRaw($field->hasValue())
            ->selectRaw($field->key() . ' as key, ' . $field->column . ' as spelling, count(*) as n')
            ->groupBy(DB::raw('1'), DB::raw('2'));

        return DB::query()->fromSub($spellings, 's')
            ->selectRaw(
                'key, sum(n)::bigint as record_count, '
                . "lpad(sum(n)::text, 19, '0') || ' ' || key as count_key, "
                . "json_agg(json_build_object('value', spelling, 'record_count', n) "
                . 'ORDER BY n DESC, spelling COLLATE "C") as spellings'
            )
            ->groupBy('key');
    }

    /**
     * The match set's records (A2 spec §5.4), over the whole set, in one query.
     *
     * @return array{total: int, with_value: int, without_value: int, keys: int}
     */
    public static function records(RecordFilters $f, VocabularyField $field): array
    {
        $has = $field->hasValue();
        $row = RecordQuery::build($f)->selectRaw(
            "count(*) as total, count(*) filter (where $has) as with_value, "
            . 'count(distinct ' . $field->key() . ") filter (where $has) as keys"
        )->first();

        $total = (int) $row->total;
        $withValue = (int) $row->with_value;

        return [
            'total' => $total,
            'with_value' => $withValue,
            'without_value' => $total - $withValue,
            'keys' => (int) $row->keys,
        ];
    }

    /** Every row of grouped, by record_count descending then key under COLLATE "C". Unpaged. */
    public static function all(RecordFilters $f, VocabularyField $field): array
    {
        return self::grouped($f, $field)
            ->orderByRaw('record_count desc, key COLLATE "C"')
            ->get()
            ->all();
    }

    /**
     * One page of grouped, keyset-paged. The key is unique, so the id expression is the constant 0, cast so ORDER BY does not read it as a column position.
     * Runs inside the caller's transaction, after RecordQuery::prepareFuzzy.
     *
     * @return array{rows: array, next_cursor: ?string, per_page: int}
     */
    public static function page(RecordFilters $f, VocabularyField $field, Sort $sort, Paging $paging, string $hash): array
    {
        $query = DB::query()->fromSub(self::grouped($f, $field), 'g')->select('g.*');
        list($keyExpr, $keyType) = self::SORT_KEYS[$sort->key];

        return KeysetPager::page($query, $keyExpr, $keyType, $sort, $paging, $hash, '0::bigint');
    }
}
