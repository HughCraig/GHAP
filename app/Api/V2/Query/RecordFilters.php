<?php

namespace TLCMap\Api\V2\Query;

use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\Bbox;
use TLCMap\Api\V2\Params\DateBound;
use TLCMap\Api\V2\Params\ExtendedDataFilter;
use TLCMap\Api\V2\Params\LayerIds;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Polygon;
use TLCMap\Api\V2\Params\Sources;

/**
 * The record filters of spec §6.4 and §7, parsed and validated once per request. Every endpoint
 * that filters records builds one of these; RecordQuery turns it into SQL. The properties are
 * read-only by convention.
 */
class RecordFilters
{
    public const MATCHES = ['exact', 'contains', 'fuzzy'];

    /** @var string|null */
    public $name;
    /** @var string|null exact, contains or fuzzy; null when no name is given */
    public $match;
    /** @var string[] source keys, in Sources::ALL order */
    public $sources;
    /** @var int[] datasource ids of $sources, looked up once */
    public $sourceIds;
    /** @var int[]|null */
    public $layers;
    /** @var Bbox|null */
    public $bbox;
    /** @var Polygon|null */
    public $polygon;
    /** @var DateBound|null */
    public $dateFrom;
    /** @var DateBound|null */
    public $dateTo;
    /** @var bool */
    public $includeUndated;
    /** @var string|null */
    public $state;
    /** @var string|null */
    public $lga;
    /** @var string|null */
    public $featureTerm;
    /** @var string|null */
    public $recordType;
    /** @var ExtendedDataFilter|null */
    public $extendedData;

    private function __construct()
    {
    }

    /** Every record of one layer, nothing else filtered; a missing or private layer is refused (spec §10.3). */
    public static function forLayer(int $layerId): self
    {
        $f = new self();
        $f->sources = Sources::ALL;
        $f->sourceIds = [];
        $byKey = Sources::idsByKey();
        foreach ($f->sources as $key) {
            if (isset($byKey[$key])) {
                $f->sourceIds[] = $byKey[$key];
            }
        }
        $f->layers = [$layerId];
        LayerGate::assertPublic($f->layers);
        $f->includeUndated = true;

        return $f;
    }

    /**
     * @param string[]|null $only the names to read as record filters, the rest left at their
     *                            defaults; null reads them all. The catalogue passes its
     *                            record-level names, since its `record_type` is a layer filter.
     */
    public static function fromParams(ParameterSet $p, ?array $only = null): self
    {
        if ($only !== null) {
            $p = $p->only($only);
        }
        $f = new self();

        $f->name = $p->string('name');
        if ($p->has('match') && $f->name === null) {
            throw ApiException::invalidParameter(
                'match',
                'requires',
                'name',
                "The parameter 'match' can only be given with 'name'."
            );
        }
        $f->match = $f->name === null ? null : $p->enum('match', self::MATCHES, 'exact');

        $f->sources = $p->has('sources') ? Sources::parse($p->raw('sources')) : Sources::ALL;
        $byKey = Sources::idsByKey();
        $f->sourceIds = [];
        foreach ($f->sources as $key) {
            if (isset($byKey[$key])) {
                $f->sourceIds[] = $byKey[$key];
            }
        }

        if ($p->has('layers')) {
            $f->layers = LayerIds::parse($p->raw('layers'), (int) config('api_v2.layers_max'));
            LayerGate::assertPublic($f->layers);
        }

        if ($p->has('bbox')) {
            $f->bbox = Bbox::parse($p->raw('bbox'));
        }
        if ($p->has('polygon')) {
            $f->polygon = Polygon::parse($p->raw('polygon'), (int) config('api_v2.polygon_max_vertices'));
            RecordQuery::assertValidPolygon($f->polygon);
        }

        if ($p->has('date_from')) {
            $f->dateFrom = DateBound::parse('date_from', $p->raw('date_from'));
        }
        if ($p->has('date_to')) {
            $f->dateTo = DateBound::parse('date_to', $p->raw('date_to'));
        }
        // Later means the ranges cannot meet: date_from begins after date_to has ended.
        if ($f->dateFrom !== null && $f->dateTo !== null && $f->dateFrom->start > $f->dateTo->end) {
            throw ApiException::invalidParameter(
                'date_from',
                'order',
                'a date no later than date_to',
                "The parameter 'date_from' must not be later than 'date_to'."
            );
        }
        $f->includeUndated = $p->bool('include_undated', true);

        $f->state = $p->string('state');
        $f->lga = $p->string('lga');
        $f->featureTerm = $p->string('feature_term');
        $f->recordType = $p->string('record_type');

        if ($p->has('extended_data')) {
            $f->extendedData = ExtendedDataFilter::parse(
                $p->raw('extended_data'),
                (int) config('api_v2.extended_data_max_clauses')
            );
        }

        return $f;
    }

    /** Whether any filter is given that tests a record's position or date (spec §10.2). */
    public function hasRecordLevelFilter(): bool
    {
        return $this->bbox !== null || $this->polygon !== null
            || $this->dateFrom !== null || $this->dateTo !== null;
    }

    /** Every record filter with its effective value, or null when not given (spec §5.2). */
    public function toEcho(): array
    {
        return [
            'name' => $this->name,
            'match' => $this->match,
            'sources' => $this->sources,
            'layers' => $this->layers,
            'bbox' => $this->bbox === null ? null : $this->bbox->toArray(),
            'polygon' => $this->polygon === null ? null : $this->polygon->toArray(),
            'date_from' => $this->dateFrom === null ? null : $this->dateFrom->text,
            'date_to' => $this->dateTo === null ? null : $this->dateTo->text,
            'include_undated' => $this->includeUndated,
            'state' => $this->state,
            'lga' => $this->lga,
            'feature_term' => $this->featureTerm,
            'record_type' => $this->recordType,
            'extended_data' => $this->extendedData === null ? null : $this->extendedData->toEcho(),
        ];
    }
}
