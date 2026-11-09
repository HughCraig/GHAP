<?php

namespace TLCMap\Api\V2\Serialise;

/** Shapes a LayerStatistics row into the `statistics` object of spec §6.2; every key is present. */
class StatisticsSerialiser
{
    /** @param array $featureTerms the `feature_terms` object */
    public static function statistics(\stdClass $row, array $featureTerms): array
    {
        $hasPoints = $row->min_lon !== null;
        $area = $row->area_m2 === null ? null : (float) $row->area_m2 / 1e6;
        $withCoordinates = (int) $row->with_coordinates;

        return [
            'count' => (int) $row->records,
            'basis' => [
                'records' => (int) $row->records,
                'with_coordinates' => $withCoordinates,
                'dated' => (int) $row->dated,
            ],
            'extent' => $hasPoints
                ? [(float) $row->min_lon, (float) $row->min_lat, (float) $row->max_lon, (float) $row->max_lat]
                : null,
            'centroid' => $row->centroid_lon === null
                ? null
                : [(float) $row->centroid_lon, (float) $row->centroid_lat],
            'convex_hull' => $row->hull === null ? null : json_decode($row->hull, true),
            'area_km2' => $area,
            'density_per_km2' => $area === null || $area == 0 ? null : $withCoordinates / $area,
            'date_range' => $row->date_start === null ? null
                : ['start' => (int) $row->date_start, 'end' => (int) $row->date_end],
            'median_date' => $row->median_date === null ? null : (int) $row->median_date,
            'mean_date' => $row->mean_date === null ? null : (int) $row->mean_date,
            'undated' => [
                'total' => (int) $row->undated_total,
                'no_date' => (int) $row->no_date,
                'unparseable_date' => (int) $row->unparseable_date,
            ],
            'unparseable_dates' => (int) $row->unparseable_dates,
            'feature_terms' => $featureTerms,
        ];
    }
}
