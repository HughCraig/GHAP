<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use TLCMap\Api\V2\Params\Sources;

/**
 * The summary of a whole match set (spec §8), computed in SQL: one aggregate query and one
 * per-layer group. Nothing here is computed from a page of results.
 */
class Summary
{
    /** @param Builder $filtered an unselected RecordQuery builder; cloned, never mutated */
    public static function compute(Builder $filtered, bool $withByLayer): array
    {
        $sourceKeys = Sources::keyById();
        $selects = [
            'count(*) as total',
            'ST_XMin(ST_Extent(r.geom)) as min_lon',
            'ST_YMin(ST_Extent(r.geom)) as min_lat',
            'ST_XMax(ST_Extent(r.geom)) as max_lon',
            'ST_YMax(ST_Extent(r.geom)) as max_lat',
            'count(*) filter (where r.geom is null) as no_coordinates',
            'min(' . RecordQuery::R_LO . ') as date_start',
            'max(' . RecordQuery::R_HI . ') as date_end',
            'count(*) filter (where ' . RecordQuery::R_LO . ' is null) as undated_total',
            'count(*) filter (where ' . RecordQuery::R_NO_DATE . ') as no_date',
            'count(*) filter (where ' . RecordQuery::R_UNPARSEABLE_SIDE . ') as unparseable_dates',
            'count(*) filter (where ' . RecordQuery::R_LO . ' is null and not ' . RecordQuery::R_NO_DATE . ') as unparseable_date',
        ];
        foreach ($sourceKeys as $id => $key) {
            $selects[] = 'count(*) filter (where r.datasource_id = ' . (int) $id . ') as source_' . (int) $id;
        }

        // Each record's dates are computed once, then aggregated (RecordQuery::withDates).
        $row = RecordQuery::withDates($filtered, ['i.geom', 'i.datasource_id'])
            ->selectRaw(implode(', ', $selects))
            ->first();

        $bySource = [];
        foreach ($sourceKeys as $id => $key) {
            $bySource[$key] = (int) $row->{'source_' . $id};
        }

        $summary = [
            'total' => (int) $row->total,
            'extent' => $row->min_lon === null ? null
                : [(float) $row->min_lon, (float) $row->min_lat, (float) $row->max_lon, (float) $row->max_lat],
            'no_coordinates_count' => (int) $row->no_coordinates,
            'date_range' => $row->date_start === null ? null
                : ['start' => (int) $row->date_start, 'end' => (int) $row->date_end],
            'undated' => [
                'total' => (int) $row->undated_total,
                'no_date' => (int) $row->no_date,
                'unparseable_date' => (int) $row->unparseable_date,
            ],
            'unparseable_dates' => (int) $row->unparseable_dates,
            'by_source' => $bySource,
        ];

        if ($withByLayer) {
            $summary['by_layer'] = self::byLayer($filtered);
        }

        return $summary;
    }

    private static function byLayer(Builder $filtered): array
    {
        $rows = (clone $filtered)
            ->whereNotNull('i.dataset_id')
            ->selectRaw(
                'i.dataset_id as layer_id, d.name as name, count(*) as count, '
                . "CASE WHEN btrim(d.warning, E' \t\r\n') = '' THEN NULL ELSE d.warning END as warning"
            )
            ->groupBy('i.dataset_id', 'd.name', 'd.warning')
            ->orderBy('i.dataset_id')
            ->get();

        $layers = [];
        foreach ($rows as $r) {
            $layers[] = [
                'layer_id' => (int) $r->layer_id,
                'name' => $r->name,
                'count' => (int) $r->count,
                'warning' => $r->warning,
            ];
        }
        return $layers;
    }
}
