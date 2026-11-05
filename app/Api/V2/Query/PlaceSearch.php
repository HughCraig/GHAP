<?php

namespace TLCMap\Api\V2\Query;

use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\Sample;
use TLCMap\Api\V2\Params\Sort;
use TLCMap\Api\V2\Serialise\FeatureSerialiser;

/** The records of a place search (spec §10.1): one keyset page, or one seeded sample. */
class PlaceSearch
{
    /** The parameters of spec §10.1, in echo order. */
    public const ALLOWED = [
        'name', 'match', 'sources', 'layers', 'bbox', 'polygon', 'date_from', 'date_to',
        'include_undated', 'state', 'lga', 'feature_term', 'record_type', 'extended_data',
        'summary_only', 'sort', 'direction', 'nulls', 'limit', 'sample', 'seed', 'cursor', 'per_page',
    ];

    /** Sort name => [key expression, cursor key type]; trusted SQL with no bindings. */
    public const SORT_KEYS = [
        'id' => ['i.id', 'int'],
        'title' => ['i.title', 'string'],
        'start' => [RecordQuery::START_KEY, 'nullable_int'],
        'end' => [RecordQuery::END_KEY, 'nullable_int'],
    ];

    public const DEFAULT_SORT = 'id';

    private const ID = 'i.id';

    /**
     * Runs inside the caller's transaction, after RecordQuery::prepareFuzzy.
     *
     * @return array{rows: array, next_cursor: ?string, per_page: int}
     */
    public static function run(RecordFilters $f, Sort $sort, Paging $paging, ?Sample $sample, string $hash): array
    {
        $query = RecordQuery::build($f)->select(FeatureSerialiser::columns());

        if ($sample !== null) {
            // One page, no cursor (spec §5.5); the id makes the order total should two hashes tie.
            $rows = $query
                ->orderByRaw('md5(CAST(' . self::ID . ' AS text) || CAST(? AS text)), ' . self::ID, [$sample->seed])
                ->limit($sample->size)
                ->get()
                ->all();

            return ['rows' => $rows, 'next_cursor' => null, 'per_page' => $paging->perPage];
        }

        list($keyExpr, $keyType) = self::SORT_KEYS[$sort->key];

        return KeysetPager::page($query, $keyExpr, $keyType, $sort, $paging, $hash, self::ID);
    }
}
