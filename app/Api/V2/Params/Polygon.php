<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;

/** A polygon given as "lon lat,lon lat,…"; the ring is closed on parsing. */
class Polygon
{
    /** @var array<int,array<int,float>> */
    private $given;
    /** @var array<int,array<int,float>> */
    private $ring;

    private function __construct(array $given, array $ring)
    {
        $this->given = $given;
        $this->ring = $ring;
    }

    public static function parse(string $value, int $maxVertices): self
    {
        $accepts = 'lon lat,lon lat,lon lat,…';
        $items = explode(',', $value);
        if (count($items) > $maxVertices) {
            throw ApiException::invalidParameter(
                'polygon',
                'range',
                "at most $maxVertices vertices",
                "The parameter 'polygon' must have at most $maxVertices vertices."
            );
        }
        $given = [];
        foreach ($items as $item) {
            $nums = preg_split('/\s+/', trim($item));
            if (count($nums) !== 2) {
                throw ApiException::invalidParameter(
                    'polygon',
                    'format',
                    $accepts,
                    "The parameter 'polygon' must be comma-separated vertices, each written as 'lon lat'."
                );
            }
            foreach ($nums as $n) {
                if (!preg_match(Bbox::NUMBER, $n)) {
                    throw ApiException::invalidParameter(
                        'polygon',
                        'format',
                        $accepts,
                        "The parameter 'polygon' must contain only plain decimal numbers."
                    );
                }
            }
            $lon = (float) $nums[0];
            $lat = (float) $nums[1];
            if ($lon < -180 || $lon > 180 || $lat < -90 || $lat > 90) {
                throw ApiException::invalidParameter(
                    'polygon',
                    'range',
                    'longitude −180–180, latitude −90–90',
                    "The parameter 'polygon' has a coordinate outside longitude −180 to 180 or latitude −90 to 90."
                );
            }
            $given[] = [$lon, $lat];
        }
        $ring = $given;
        if ($ring[0] !== $ring[count($ring) - 1]) {
            $ring[] = $ring[0];
        }
        $distinct = [];
        foreach ($ring as $v) {
            $distinct[$v[0] . ' ' . $v[1]] = true;
        }
        if (count($distinct) < 3) {
            throw ApiException::invalidParameter(
                'polygon',
                'vertices',
                'at least three distinct vertices',
                "The parameter 'polygon' must have at least three distinct vertices."
            );
        }
        return new self($given, $ring);
    }

    public function vertices(): array
    {
        return $this->ring;
    }

    public function toArray(): array
    {
        return $this->given;
    }
}
