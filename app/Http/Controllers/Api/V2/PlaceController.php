<?php

namespace TLCMap\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Params\Cursor;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Sample;
use TLCMap\Api\V2\Params\Sort;
use TLCMap\Api\V2\Params\Sources;
use TLCMap\Api\V2\Query\PlaceSearch;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\RecordQuery;
use TLCMap\Api\V2\Query\Summary;
use TLCMap\Api\V2\Serialise\FeatureSerialiser;
use TLCMap\Api\V2\Support\Json;
use TLCMap\Http\Controllers\Controller;

/** GET /api/v2/places — place search (spec §10.1). */
class PlaceController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        // One transaction, so a fuzzy search's similarity threshold covers every query it serves.
        return DB::transaction(function () use ($request) {
            $p = ParameterSet::fromRequest($request, PlaceSearch::ALLOWED);
            $filters = RecordFilters::fromParams($p);
            $summaryOnly = $p->bool('summary_only', false);
            $sort = Sort::parse($p, array_keys(PlaceSearch::SORT_KEYS), PlaceSearch::DEFAULT_SORT);
            $paging = Paging::parse(
                $p,
                (int) config('api_v2.features_per_page'),
                (int) config('api_v2.features_per_page_max')
            );
            $sample = Sample::parse($p, (int) config('api_v2.sample_max'));

            $query = self::echoQuery($filters, $summaryOnly, $sort, $paging, $sample);

            RecordQuery::prepareFuzzy($filters);
            $summary = Summary::compute(RecordQuery::build($filters), true);

            if ($summaryOnly) {
                return Json::response(['query' => $query, 'summary' => $summary]);
            }

            $result = PlaceSearch::run($filters, $sort, $paging, $sample, Cursor::queryHash($query));

            $keyById = Sources::keyById();
            $features = [];
            foreach ($result['rows'] as $row) {
                $features[] = FeatureSerialiser::feature($row, $keyById);
            }

            return Json::response([
                'type' => 'FeatureCollection',
                'query' => $query,
                'summary' => $summary,
                'page' => [
                    'returned' => count($features),
                    'per_page' => $result['per_page'],
                    'next_cursor' => $result['next_cursor'],
                    'next' => Paging::nextUrl($request, $result['next_cursor']),
                ],
                'features' => $features,
            ]);
        });
    }

    /** Every parameter of the endpoint with its effective value, null when not given (spec §5.2). */
    private static function echoQuery(
        RecordFilters $filters,
        bool $summaryOnly,
        Sort $sort,
        Paging $paging,
        ?Sample $sample
    ): array {
        $query = array_intersect_key($filters->toEcho(), array_flip(PlaceSearch::ALLOWED));
        $query['summary_only'] = $summaryOnly;
        $query += $sort->toEcho();
        $query['limit'] = $paging->limit;
        $query['sample'] = $sample === null ? null : $sample->size;
        $query['seed'] = $sample === null ? null : $sample->seed;
        $query['cursor'] = $paging->cursor;
        $query['per_page'] = $paging->perPage;

        return $query;
    }
}
