<?php

namespace TLCMap\Api\V2\Serialise;

use Illuminate\Http\Request;
use TLCMap\Api\V2\Params\Cursor;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\Sources;

/** The parts of a paged feature response that every record endpoint shares (spec §5.3, §9). */
class FeatureCollection
{
    /**
     * Rejects a cursor that does not decode or belongs to another query, before any query runs,
     * so a summary-only request cannot ignore one silently (spec §5.1, §5.3).
     */
    public static function assertCursor(?string $cursor, string $hash, string $keyType): void
    {
        if ($cursor !== null) {
            Cursor::decode($cursor, $hash, $keyType);
        }
    }

    /**
     * @param array $result KeysetPager's or a sample's {rows, next_cursor, per_page}
     * @return array{features: array, page: array}
     */
    public static function build(Request $request, array $result): array
    {
        $keyById = Sources::keyById();
        $features = [];
        foreach ($result['rows'] as $row) {
            $features[] = FeatureSerialiser::feature($row, $keyById);
        }

        return [
            'features' => $features,
            'page' => [
                'returned' => count($features),
                'per_page' => $result['per_page'],
                'next_cursor' => $result['next_cursor'],
                'next' => Paging::nextUrl($request, $result['next_cursor']),
            ],
        ];
    }
}
