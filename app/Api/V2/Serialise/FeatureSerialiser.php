<?php

namespace TLCMap\Api\V2\Serialise;

use Carbon\Carbon;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use TLCMap\Api\V2\Query\RecordQuery;

/** Turns one record row into an API v2 GeoJSON feature (spec §9). */
class FeatureSerialiser
{
    /** Select items for the record query; PHP constants cannot hold objects, so this is a method. */
    public static function columns(): array
    {
        $items = [
            'i.id', 'i.uid', 'i.title', 'i.placename', 'i.description', 'i.datasource_id', 'i.dataset_id',
            'rt.type as record_type', 'i.state', 'i.lga', 'i.parish', 'i.feature_term', 'i.datestart',
            'i.dateend', RecordQuery::S . ' as udatestart', RecordQuery::E . ' as udateend', 'i.source',
            'i.external_url', 'i.image_path', 'i.glycerine_url', 'i.created_at', 'i.updated_at',
            'i.extended_data', 'i.latitude', 'i.longitude',
        ];
        return array_map(function (string $item): Expression {
            return DB::raw($item);
        }, $items);
    }

    /** @param array $keyById datasource id => source key */
    public static function feature(\stdClass $row, array $keyById): array
    {
        [$extended, $unreadable] = self::extendedData($row->extended_data ?? null);

        $hasPoint = ($row->latitude ?? null) !== null && ($row->longitude ?? null) !== null;
        $geometry = $hasPoint
            ? ['type' => 'Point', 'coordinates' => [(float) $row->longitude, (float) $row->latitude]]
            : null;

        $image = self::text($row->image_path ?? null);

        return [
            'type' => 'Feature',
            'geometry' => $geometry,
            'properties' => [
                'id' => $row->uid,
                'name' => self::text($row->title ?? null),
                'placename' => self::text($row->placename ?? null),
                'description' => self::text($row->description ?? null),
                'datasource' => $keyById[(int) $row->datasource_id] ?? null,
                'layer_id' => self::int($row->dataset_id ?? null),
                'record_type' => self::text($row->record_type ?? null),
                'state' => self::text($row->state ?? null),
                'lga' => self::text($row->lga ?? null),
                'parish' => self::text($row->parish ?? null),
                'feature_term' => self::text($row->feature_term ?? null),
                'datestart' => self::text($row->datestart ?? null),
                'dateend' => self::text($row->dateend ?? null),
                'udatestart' => self::int($row->udatestart ?? null),
                'udateend' => self::int($row->udateend ?? null),
                'source' => self::text($row->source ?? null),
                'external_url' => self::text($row->external_url ?? null),
                'image_url' => $image === null ? null : Storage::disk('public')->url('images/' . $image),
                'glycerine_url' => self::text($row->glycerine_url ?? null),
                'url' => url('places/' . $row->uid),
                'created_at' => self::timestamp($row->created_at ?? null),
                'updated_at' => self::timestamp($row->updated_at ?? null),
                'extended_data' => self::asObject($extended),
                'extended_data_unreadable' => $unreadable,
            ],
        ];
    }

    /** An empty or list-shaped map would encode as a JSON array; keep it an object. */
    private static function asObject(array $fields)
    {
        return $fields && array_keys($fields) !== range(0, count($fields) - 1) ? $fields : (object) $fields;
    }

    /** Null for absent or empty values; anything else verbatim. */
    private static function text($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (string) $value;
    }

    private static function int($value): ?int
    {
        return $value === null ? null : (int) $value;
    }

    /** Stored in the application timezone; output as ISO 8601 UTC. */
    private static function timestamp($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        return Carbon::parse($value, config('app.timezone'))->utc()->format('Y-m-d\TH:i:s\Z');
    }

    /** @return array [name => value map, unreadable flag]; the first Data element of a name wins. */
    private static function extendedData($stored): array
    {
        if ($stored === null || trim((string) $stored) === '') {
            return [[], false];
        }
        $previous = libxml_use_internal_errors(true);
        try {
            $xml = simplexml_load_string((string) $stored, \SimpleXMLElement::class, LIBXML_NONET);
            libxml_clear_errors();
        } finally {
            libxml_use_internal_errors($previous);
        }
        if ($xml === false) {
            return [[], true];
        }
        $fields = [];
        foreach ($xml->Data as $data) {
            $name = (string) $data['name'];
            if (!array_key_exists($name, $fields)) {
                $fields[$name] = (string) $data->value;
            }
        }
        return [$fields, false];
    }
}
