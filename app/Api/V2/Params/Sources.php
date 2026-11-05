<?php

namespace TLCMap\Api\V2\Params;

use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Errors\ApiException;

/** The four kinds of source a v2 search can draw on. */
class Sources
{
    public const ALL = ['layers', 'anps', 'ncg', 'text'];

    public const PARAM_NAMES = [
        'layers' => 'searchpublicdatasets',
        'anps' => 'searchausgaz',
        'ncg' => 'searchncg',
        'text' => 'searchgeocoder',
    ];

    /** Unique source keys from a comma-separated list, in ALL order. */
    public static function parse(string $value): array
    {
        $accepts = implode(', ', self::ALL);
        $given = [];
        foreach (explode(',', $value) as $item) {
            $item = trim($item);
            if ($item === '') {
                continue;
            }
            if (!in_array($item, self::ALL, true)) {
                throw ApiException::invalidParameter(
                    'sources',
                    'one_of',
                    $accepts,
                    "The parameter 'sources' must contain only: $accepts."
                );
            }
            $given[$item] = true;
        }
        if (!$given) {
            throw ApiException::invalidParameter(
                'sources',
                'empty',
                $accepts,
                "The parameter 'sources' must name at least one source."
            );
        }
        return array_values(array_filter(self::ALL, function ($k) use ($given) {
            return isset($given[$k]);
        }));
    }

    /** @return array<string,int> */
    public static function idsByKey(): array
    {
        $byParam = DB::table('tlcmap.datasource')->pluck('id', 'search_param_name')->all();
        $out = [];
        foreach (self::PARAM_NAMES as $key => $param) {
            if (isset($byParam[$param])) {
                $out[$key] = (int) $byParam[$param];
            }
        }
        return $out;
    }

    /** @return array<int,string> */
    public static function keyById(): array
    {
        return array_flip(self::idsByKey());
    }
}
