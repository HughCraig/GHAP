<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;

/** A comma-separated list of positive layer ids. */
class LayerIds
{
    /** @return int[] unique, in the order given */
    public static function parse(string $value, int $max): array
    {
        $items = explode(',', $value);
        if (count($items) > $max) {
            throw ApiException::invalidParameter(
                'layers',
                'range',
                "at most $max ids",
                "The parameter 'layers' must list at most $max layer ids."
            );
        }
        $ids = [];
        foreach ($items as $item) {
            $item = trim($item);
            if (!preg_match('/^[0-9]{1,18}$/D', $item) || (int) $item < 1) {
                throw ApiException::invalidParameter(
                    'layers',
                    'integer',
                    'positive whole numbers, comma-separated',
                    "The parameter 'layers' must be a comma-separated list of positive whole numbers."
                );
            }
            $ids[(int) $item] = true;
        }
        return array_keys($ids);
    }
}
