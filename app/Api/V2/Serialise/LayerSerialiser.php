<?php

namespace TLCMap\Api\V2\Serialise;

use Carbon\Carbon;

/** Turns one dataset row into the API v2 layer object (spec §10.3). */
class LayerSerialiser
{
    /** @param string[] $keywords already ordered */
    public static function layer(\stdClass $dataset, array $keywords, ?string $recordType): array
    {
        return [
            'id' => (int) $dataset->id,
            'name' => self::text($dataset->name ?? null),
            'description' => self::text($dataset->description ?? null),
            'subject_keywords' => array_values($keywords),
            'record_type' => self::text($recordType),
            'creator' => self::text($dataset->creator ?? null),
            'publisher' => self::text($dataset->publisher ?? null),
            'contact' => self::text($dataset->contact ?? null),
            'citation' => self::text($dataset->citation ?? null),
            'doi' => self::text($dataset->doi ?? null),
            'source_url' => self::text($dataset->source_url ?? null),
            'linkback' => self::text($dataset->linkback ?? null),
            'license' => self::text($dataset->license ?? null),
            'rights' => self::text($dataset->rights ?? null),
            'language' => self::text($dataset->language ?? null),
            'warning' => self::text($dataset->warning ?? null),
            'declared' => [
                'latitude_from' => self::float($dataset->latitude_from ?? null),
                'latitude_to' => self::float($dataset->latitude_to ?? null),
                'longitude_from' => self::float($dataset->longitude_from ?? null),
                'longitude_to' => self::float($dataset->longitude_to ?? null),
                'temporal_from' => self::text($dataset->temporal_from ?? null),
                'temporal_to' => self::text($dataset->temporal_to ?? null),
            ],
            'created' => self::date($dataset->created ?? null),
            'created_at' => self::timestamp($dataset->created_at ?? null),
            'updated_at' => self::timestamp($dataset->updated_at ?? null),
            'url' => url('layers/' . (int) $dataset->id),
        ];
    }

    /**
     * One catalogue entry (spec §10.2) from a LayerCatalogue row. The figures describe the whole
     * layer; `matching_count` is null unless record-level filters were given.
     */
    public static function catalogueEntry(\stdClass $row, bool $withMatching): array
    {
        return [
            'id' => (int) $row->id,
            'name' => self::text($row->name),
            'description' => self::text($row->description),
            'creator' => self::text($row->creator),
            'record_type' => self::text($row->record_type),
            'record_count' => (int) $row->record_count,
            'matching_count' => $withMatching ? (int) $row->matching_count : null,
            'bbox' => $row->min_lon === null ? null
                : [(float) $row->min_lon, (float) $row->min_lat, (float) $row->max_lon, (float) $row->max_lat],
            'date_range' => $row->date_start === null ? null
                : ['start' => (int) $row->date_start, 'end' => (int) $row->date_end],
            'undated' => [
                'total' => (int) $row->undated_total,
                'no_date' => (int) $row->no_date,
                'unparseable_date' => (int) $row->unparseable_date,
            ],
            'has_license' => (bool) $row->has_license,
            'has_warning' => (bool) $row->has_warning,
            'url' => url('layers/' . (int) $row->id),
        ];
    }

    /** Null for null or blank (only whitespace); anything else verbatim, never trimmed. */
    private static function text($value): ?string
    {
        if ($value === null || trim((string) $value, " \t\r\n") === '') {
            return null;
        }
        return (string) $value;
    }

    private static function float($value): ?float
    {
        return $value === null ? null : (float) $value;
    }

    private static function date($value): ?string
    {
        return $value === null || $value === '' ? null : Carbon::parse($value)->format('Y-m-d');
    }

    /** Stored in the application timezone; output as ISO 8601 UTC. */
    private static function timestamp($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return Carbon::parse($value, config('app.timezone'))->utc()->format('Y-m-d\TH:i:s\Z');
    }
}
