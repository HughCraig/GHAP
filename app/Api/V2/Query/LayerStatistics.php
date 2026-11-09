<?php

namespace TLCMap\Api\V2\Query;

/**
 * A layer's statistics (spec §6), computed in SQL in one aggregate query over all the layer's
 * records. Each record's dates are computed once (RecordQuery::withDates) and then aggregated;
 * the date columns are added to the same select list.
 */
class LayerStatistics
{
    /** @return \stdClass one row of figures; the serialiser shapes it */
    public static function compute(RecordFilters $f): \stdClass
    {
        $hull = 'ST_ConvexHull(ST_Collect(r.geom))';
        $selects = [
            'count(*) as records',
            'count(r.geom) as with_coordinates',
            'count(*) filter (where ' . RecordQuery::R_LO . ' is not null) as dated',
            'ST_XMin(ST_Extent(r.geom)) as min_lon',
            'ST_YMin(ST_Extent(r.geom)) as min_lat',
            'ST_XMax(ST_Extent(r.geom)) as max_lon',
            'ST_YMax(ST_Extent(r.geom)) as max_lat',
            'ST_X(ST_Centroid(ST_Collect(r.geom)::geography)::geometry) as centroid_lon',
            'ST_Y(ST_Centroid(ST_Collect(r.geom)::geography)::geometry) as centroid_lat',
            'ST_AsGeoJSON(' . $hull . ') as hull',
            'ST_Area(' . $hull . '::geography) as area_m2',
        ];

        return RecordQuery::withDates(RecordQuery::build($f), ['i.geom'])
            ->selectRaw(implode(', ', $selects))
            ->first();
    }
}
