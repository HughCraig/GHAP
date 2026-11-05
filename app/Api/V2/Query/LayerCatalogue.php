<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\Sort;

/**
 * The catalogue of public layers (spec §10.2). Record-level filters reach a layer only through
 * RecordQuery, grouped by layer, so the catalogue lists exactly the layers the equivalent place
 * search draws records from (spec §3.2). A layer's own figures are computed from all its records.
 */
class LayerCatalogue
{
    /** The parameters of spec §10.2, in echo order. */
    public const ALLOWED = [
        'q', 'bbox', 'polygon', 'date_from', 'date_to', 'include_undated', 'record_type', 'creator',
        'has_license', 'has_warning', 'min_records', 'sort', 'direction', 'cursor', 'per_page',
    ];

    /** The parameters read as record filters; every other filter is a layer's own. */
    public const RECORD_LEVEL = ['bbox', 'polygon', 'date_from', 'date_to', 'include_undated'];

    /**
     * Sort name => [key expression, cursor key type]; trusted SQL with no bindings, over the
     * derived table `d` that holds one row per layer.
     */
    public const SORT_KEYS = [
        'id' => ['d.id', 'int'],
        'name' => ['d.name', 'nullable_string'],
    ];

    public const DEFAULT_SORT = 'id';

    private const ID = 'd.id';

    /** Non-blank, trimming the same whitespace as the serialisers (spaces, tabs, CR, LF). */
    private const HAS_LICENSE = 'nullif(btrim(d.license, E\' \t\r\n\'), \'\') IS NOT NULL';
    private const HAS_WARNING = 'nullif(btrim(d.warning, E\' \t\r\n\'), \'\') IS NOT NULL';

    /**
     * Runs inside the caller's transaction.
     *
     * @param array $layerFilters q, record_type, creator, has_license, has_warning, min_records;
     *                            null or absent when not given
     * @return array{total: int, rows: array, next_cursor: ?string, per_page: int}
     */
    public static function run(array $layerFilters, RecordFilters $records, Sort $sort, Paging $paging, string $hash): array
    {
        $layers = DB::query()->fromSub(self::layers($layerFilters, $records), 'd');
        $total = (clone $layers)->count();

        list($keyExpr, $keyType) = self::SORT_KEYS[$sort->key];
        $page = KeysetPager::page($layers->select('d.*'), $keyExpr, $keyType, $sort, $paging, $hash, self::ID);

        return ['total' => $total] + $page;
    }

    /** One row per matching public layer, with its whole-layer figures and its matching count. */
    private static function layers(array $filters, RecordFilters $records): Builder
    {
        $withMatching = $records->hasRecordLevelFilter();

        $q = DB::table('tlcmap.dataset as d')
            ->leftJoin('tlcmap.recordtype as rt', 'rt.id', '=', 'd.recordtype_id')
            ->leftJoinSub(self::aggregates(), 'a', 'a.dataset_id', '=', 'd.id')
            ->whereRaw('d.public = true')
            ->selectRaw(implode(', ', [
                'd.id', 'd.name', 'd.description', 'd.creator', 'rt.type as record_type',
                'coalesce(a.record_count, 0) as record_count',
                ($withMatching ? 'm.matching_count' : 'CAST(NULL AS bigint)') . ' as matching_count',
                'a.min_lon', 'a.min_lat', 'a.max_lon', 'a.max_lat', 'a.date_start', 'a.date_end',
                'coalesce(a.undated_total, 0) as undated_total',
                'coalesce(a.no_date, 0) as no_date',
                'coalesce(a.unparseable_date, 0) as unparseable_date',
                '(' . self::HAS_LICENSE . ') as has_license',
                '(' . self::HAS_WARNING . ') as has_warning',
            ]));

        if ($withMatching) {
            // An inner join: a layer none of whose visible records match is not listed.
            $matching = RecordQuery::build($records)
                ->whereNotNull('i.dataset_id')
                ->groupBy('i.dataset_id')
                ->selectRaw('i.dataset_id, count(*) as matching_count');
            $q->joinSub($matching, 'm', 'm.dataset_id', '=', 'd.id');
        }

        self::applyLayerFilters($q, $filters);

        return $q;
    }

    /** Each layer's figures over all its records (spec §10.2), defined as the summary's (§8). */
    private static function aggregates(): Builder
    {
        return DB::table('tlcmap.dataitem as i')
            ->whereNotNull('i.dataset_id')
            ->groupBy('i.dataset_id')
            ->selectRaw(implode(', ', [
                'i.dataset_id',
                'count(*) as record_count',
                'ST_XMin(ST_Extent(i.geom)) as min_lon',
                'ST_YMin(ST_Extent(i.geom)) as min_lat',
                'ST_XMax(ST_Extent(i.geom)) as max_lon',
                'ST_YMax(ST_Extent(i.geom)) as max_lat',
                'min(' . RecordQuery::LO . ') as date_start',
                'max(' . RecordQuery::HI . ') as date_end',
                'count(*) filter (where ' . RecordQuery::LO . ' is null) as undated_total',
                'count(*) filter (where ' . RecordQuery::NO_DATE . ') as no_date',
                'count(*) filter (where ' . RecordQuery::LO . ' is null and not ' . RecordQuery::NO_DATE . ') as unparseable_date',
            ]));
    }

    private static function applyLayerFilters(Builder $q, array $filters): void
    {
        $like = 'ILIKE CAST(? AS text) ' . RecordQuery::ESCAPE;

        if (($filters['q'] ?? null) !== null) {
            $pattern = '%' . RecordQuery::escapeLike($filters['q']) . '%';
            $q->whereRaw(
                "(d.name $like OR d.description $like OR EXISTS ("
                . 'SELECT 1 FROM tlcmap.dataset_subject_keyword dsk'
                . ' JOIN tlcmap.subject_keyword k ON k.id = dsk.subject_keyword_id'
                . " WHERE dsk.dataset_id = d.id AND k.keyword $like))",
                [$pattern, $pattern, $pattern]
            );
        }

        if (($filters['creator'] ?? null) !== null) {
            $q->whereRaw("d.creator $like", ['%' . RecordQuery::escapeLike($filters['creator']) . '%']);
        }

        if (($filters['record_type'] ?? null) !== null) {
            $q->whereRaw('lower(rt.type) = lower(CAST(? AS text))', [$filters['record_type']]);
        }

        foreach (['has_license' => self::HAS_LICENSE, 'has_warning' => self::HAS_WARNING] as $name => $present) {
            if (($filters[$name] ?? null) !== null) {
                $q->whereRaw($filters[$name] ? $present : "NOT ($present)");
            }
        }

        if (($filters['min_records'] ?? null) !== null) {
            $q->whereRaw('coalesce(a.record_count, 0) >= CAST(? AS bigint)', [$filters['min_records']]);
        }
    }
}
