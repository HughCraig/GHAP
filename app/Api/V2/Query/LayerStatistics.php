<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Support\Facades\DB;

/**
 * A layer's statistics (spec §6), computed in SQL in one aggregate query over all the layer's
 * records. Each record's dates are computed once (RecordQuery::withDates), and its period midpoint
 * once more in a derived level over them, then everything is aggregated in the one select list.
 */
class LayerStatistics
{
    /**
     * The exact floor (toward negative infinity) of the mean of a numeric column, as bigint; null
     * when no row has a value. avg() and numeric division round at a limited scale, so a mean a
     * hair below an integer can come back as that integer; div() truncates exactly, and a negative
     * sum with a remainder is one lower.
     */
    public static function meanFloor(string $column): string
    {
        return "(div(sum($column), count($column))"
            . " - case when sum($column) < 0 and mod(sum($column), count($column)) <> 0 then 1 else 0 end)::bigint";
    }

    /** @return \stdClass one row of figures; the serialiser shapes it */
    public static function compute(RecordFilters $f): \stdClass
    {
        $hull = 'ST_ConvexHull(ST_Collect(r.geom))';
        $selects = [
            'count(*) as records',
            'count(r.geom) as with_coordinates',
            'count(*) filter (where ' . RecordQuery::R_LO . ' is not null) as dated',
            'min(' . RecordQuery::R_LO . ') as date_start',
            'max(' . RecordQuery::R_HI . ') as date_end',
            'count(*) filter (where ' . RecordQuery::R_LO . ' is null) as undated_total',
            'count(*) filter (where ' . RecordQuery::R_NO_DATE . ') as no_date',
            'count(*) filter (where ' . RecordQuery::R_UNPARSEABLE_SIDE . ') as unparseable_dates',
            'count(*) filter (where ' . RecordQuery::R_LO . ' is null and not ' . RecordQuery::R_NO_DATE . ') as unparseable_date',
            // Over dated records only (mid is null otherwise). percentile_disc ascending is the lower
            // middle value and descending the upper; they coincide for an odd count.
            self::meanFloor('r.mid') . ' as mean_date',
            'floor((percentile_disc(0.5) within group (order by r.mid)'
                . ' + percentile_disc(0.5) within group (order by r.mid desc))::numeric / 2)::bigint as median_date',
            'ST_XMin(ST_Extent(r.geom)) as min_lon',
            'ST_YMin(ST_Extent(r.geom)) as min_lat',
            'ST_XMax(ST_Extent(r.geom)) as max_lon',
            'ST_YMax(ST_Extent(r.geom)) as max_lat',
            'ST_X(ST_Centroid(ST_Collect(r.geom)::geography)::geometry) as centroid_lon',
            'ST_Y(ST_Centroid(ST_Collect(r.geom)::geography)::geometry) as centroid_lat',
            'ST_AsGeoJSON(' . $hull . ') as hull',
            'ST_Area(' . $hull . '::geography) as area_m2',
        ];

        $dates = RecordQuery::withDates(RecordQuery::build($f), ['i.geom']);
        $withMidpoint = DB::query()->fromSub(
            $dates->select('r.*')->selectRaw(
                'floor(((' . RecordQuery::R_LO . ')::numeric + (' . RecordQuery::R_HI . ')::numeric) / 2) as mid'
            ),
            'r'
        );

        return $withMidpoint
            ->selectRaw(implode(', ', $selects))
            ->first();
    }
}
