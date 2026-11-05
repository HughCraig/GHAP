<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;

/** A bounding box: minLon,minLat,maxLon,maxLat in decimal degrees. */
class Bbox
{
    public const NUMBER = '/^-?[0-9]+(\.[0-9]+)?$/D';

    /** @var float */
    public $minLon;
    /** @var float */
    public $minLat;
    /** @var float */
    public $maxLon;
    /** @var float */
    public $maxLat;

    private function __construct(float $minLon, float $minLat, float $maxLon, float $maxLat)
    {
        $this->minLon = $minLon;
        $this->minLat = $minLat;
        $this->maxLon = $maxLon;
        $this->maxLat = $maxLat;
    }

    public static function parse(string $value): self
    {
        $accepts = 'minLon,minLat,maxLon,maxLat';
        $parts = array_map('trim', explode(',', $value));
        if (count($parts) !== 4) {
            throw ApiException::invalidParameter(
                'bbox',
                'format',
                $accepts,
                "The parameter 'bbox' must be four comma-separated numbers: $accepts."
            );
        }
        foreach ($parts as $part) {
            if (!preg_match(self::NUMBER, $part)) {
                throw ApiException::invalidParameter(
                    'bbox',
                    'format',
                    $accepts,
                    "The parameter 'bbox' must contain only plain decimal numbers."
                );
            }
        }
        list($minLon, $minLat, $maxLon, $maxLat) = array_map('floatval', $parts);
        foreach ([$minLon, $maxLon] as $lon) {
            if ($lon < -180 || $lon > 180) {
                throw ApiException::invalidParameter(
                    'bbox',
                    'range',
                    'longitude −180–180, latitude −90–90',
                    "The parameter 'bbox' has a longitude outside −180 to 180."
                );
            }
        }
        foreach ([$minLat, $maxLat] as $lat) {
            if ($lat < -90 || $lat > 90) {
                throw ApiException::invalidParameter(
                    'bbox',
                    'range',
                    'longitude −180–180, latitude −90–90',
                    "The parameter 'bbox' has a latitude outside −90 to 90."
                );
            }
        }
        if ($minLat > $maxLat) {
            throw ApiException::invalidParameter(
                'bbox',
                'order',
                'minLat not greater than maxLat',
                "The parameter 'bbox' must have minLat no greater than maxLat."
            );
        }
        return new self($minLon, $minLat, $maxLon, $maxLat);
    }

    public function crossesAntimeridian(): bool
    {
        return $this->minLon > $this->maxLon;
    }

    public function toArray(): array
    {
        return [$this->minLon, $this->minLat, $this->maxLon, $this->maxLat];
    }
}
