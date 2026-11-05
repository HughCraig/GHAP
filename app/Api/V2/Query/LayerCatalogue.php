<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\Sort;

/**
 * The catalogue of public layers (spec §10.2). Record-level filters reach a layer only through
 * RecordQuery, grouped by layer, so the catalogue lists exactly the layers the equivalent place
 * search draws records from (spec §3.2). A layer's own figures are computed from all its visible
 * records.
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
     * Runs inside the caller's transaction. The layers are filtered and paged first, from what
     * needs no date functions; the figures are then computed for the returned page alone, so the
     * cost of a request follows the size of its page, not of the catalogue.
     *
     * @param array $layerFilters q, record_type, creator, has_license, has_warning, min_records;
     *                            null or absent when not given
     * @return array{total: int, rows: array, next_cursor: ?string, per_page: int}
     */
    public static function run(array $layerFilters, RecordFilters $records, Sort $sort, Paging $paging, string $hash): array
    {
        $layers = DB::query()->fromSub(self::layers($layerFilters, $records), 'd');

        list($keyExpr, $keyType) = self::SORT_KEYS[$sort->key];
        $page = KeysetPager::page($layers->select('d.*'), $keyExpr, $keyType, $sort, $paging, $hash, self::ID);

        // Every row carries the count of the whole match set, so the set is computed once. A page
        // with no rows has none to read it from, and counts separately.
        $total = $page['rows'] === [] ? (clone $layers)->count() : (int) $page['rows'][0]->_total;

        $page['rows'] = self::withFigures($page['rows']);

        return ['total' => $total] + $page;
    }

    /** One row per matching public layer, with its matching count; the figures are added later. */
    private static function layers(array $filters, RecordFilters $records): Builder
    {
        $withMatching = $records->hasRecordLevelFilter();

        $q = DB::table('tlcmap.dataset as d')
            ->leftJoin('tlcmap.recordtype as rt', 'rt.id', '=', 'd.recordtype_id')
            ->whereRaw('d.public = true')
            ->selectRaw(implode(', ', [
                'd.id', 'd.name', 'd.description', 'd.creator', 'rt.type as record_type',
                ($withMatching ? 'm.matching_count' : 'CAST(NULL AS bigint)') . ' as matching_count',
                '(' . self::HAS_LICENSE . ') as has_license',
                '(' . self::HAS_WARNING . ') as has_warning',
                // A window over the unpaged set. Pushing the outer keyset condition below it would
                // change its value, so PostgreSQL never does: this is the total on every page.
                'count(*) over () as _total',
            ]));

        if ($withMatching) {
            // An inner join: a layer none of whose visible records match is not listed.
            $matching = RecordQuery::build($records)
                ->whereNotNull('i.dataset_id')
                ->groupBy('i.dataset_id')
                ->selectRaw('i.dataset_id, count(*) as matching_count');
            $q->joinSub($matching, 'm', 'm.dataset_id', '=', 'd.id');
        }

        if (($filters['min_records'] ?? null) !== null) {
            // Each layer's visible records counted, with no date functions.
            $counts = RecordQuery::build(RecordFilters::visible())
                ->whereNotNull('i.dataset_id')
                ->groupBy('i.dataset_id')
                ->selectRaw('i.dataset_id, count(*) as record_count');
            $q->leftJoinSub($counts, 'c', 'c.dataset_id', '=', 'd.id')
                ->whereRaw('coalesce(c.record_count, 0) >= CAST(? AS bigint)', [$filters['min_records']]);
        }

        self::applyLayerFilters($q, $filters);

        return $q;
    }

    /**
     * The page's rows with each layer's figures over all its visible records (spec §10.2) — the
     * set layer detail summarises — defined as the summary's (§8), each date function called once
     * per record. One query, reading the records of the page's layers only.
     *
     * @param \stdClass[] $rows
     * @return \stdClass[]
     */
    private static function withFigures(array $rows): array
    {
        if ($rows === []) {
            return $rows;
        }
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->id;
        }

        $records = RecordQuery::build(RecordFilters::visible())->whereIn('i.dataset_id', $ids);
        $figures = RecordQuery::withDates($records, ['i.dataset_id', 'i.geom'])
            ->groupBy('r.dataset_id')
            ->selectRaw(implode(', ', [
                'r.dataset_id',
                'count(*) as record_count',
                'ST_XMin(ST_Extent(r.geom)) as min_lon',
                'ST_YMin(ST_Extent(r.geom)) as min_lat',
                'ST_XMax(ST_Extent(r.geom)) as max_lon',
                'ST_YMax(ST_Extent(r.geom)) as max_lat',
                'min(' . RecordQuery::R_LO . ') as date_start',
                'max(' . RecordQuery::R_HI . ') as date_end',
                'count(*) filter (where ' . RecordQuery::R_LO . ' is null) as undated_total',
                'count(*) filter (where ' . RecordQuery::R_NO_DATE . ') as no_date',
                'count(*) filter (where ' . RecordQuery::R_LO . ' is null and not ' . RecordQuery::R_NO_DATE . ') as unparseable_date',
            ]))
            ->get()
            ->keyBy('dataset_id');

        // A layer with no visible records has no row here: no records, no extent, no dates.
        $empty = (object) [
            'record_count' => 0, 'min_lon' => null, 'min_lat' => null, 'max_lon' => null, 'max_lat' => null,
            'date_start' => null, 'date_end' => null, 'undated_total' => 0, 'no_date' => 0, 'unparseable_date' => 0,
        ];
        foreach ($rows as $row) {
            $f = $figures->get($row->id, $empty);
            foreach ((array) $empty as $column => $unused) {
                $row->$column = $f->$column;
            }
        }

        return $rows;
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
    }
}
