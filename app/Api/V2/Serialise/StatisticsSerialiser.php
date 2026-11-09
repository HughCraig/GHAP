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
            'date_range' => null,
            'median_date' => null,
            'mean_date' => null,
            'undated' => ['total' => 0, 'no_date' => 0, 'unparseable_date' => 0],
            'unparseable_dates' => 0,
            'feature_terms' => $featureTerms,
        ];
    }
}
