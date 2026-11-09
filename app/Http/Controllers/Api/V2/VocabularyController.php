<?php

namespace TLCMap\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Params\Cursor;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Sort;
use TLCMap\Api\V2\Params\VocabularyField;
use TLCMap\Api\V2\Query\PlaceSearch;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\RecordQuery;
use TLCMap\Api\V2\Query\Vocabulary;
use TLCMap\Api\V2\Serialise\FeatureCollection;
use TLCMap\Api\V2\Serialise\VocabularySerialiser;
use TLCMap\Api\V2\Support\Json;
use TLCMap\Http\Controllers\Controller;

/** GET /api/v2/vocabulary — a field's distinct values among the matching records (A2 spec §5). */
class VocabularyController extends Controller
{
    public const ALLOWED = [
        'field',
        'name', 'match', 'sources', 'layers', 'bbox', 'polygon', 'date_from', 'date_to',
        'include_undated', 'state', 'lga', 'parish', 'feature_term', 'record_type', 'extended_data',
        'sort', 'direction', 'cursor', 'per_page',
    ];

    public function index(Request $request): JsonResponse
    {
        // One transaction, so a fuzzy search's similarity threshold covers every query it serves.
        return DB::transaction(function () use ($request) {
            $p = ParameterSet::fromRequest($request, self::ALLOWED);
            $field = VocabularyField::parse($p);
            $filters = RecordFilters::fromParams($p);
            $sort = Sort::parse($p, array_keys(Vocabulary::SORT_KEYS), Vocabulary::DEFAULT_SORT);
            $paging = Paging::parse(
                $p,
                (int) config('api_v2.vocabulary_per_page'),
                (int) config('api_v2.vocabulary_per_page_max')
            );

            $query = ['field' => $field->name]
                + array_intersect_key($filters->toEcho(), array_flip(self::ALLOWED));
            $query['sort'] = $sort->key;
            $query['direction'] = $sort->direction;
            $query['cursor'] = $paging->cursor;
            $query['per_page'] = $paging->perPage;

            $hash = Cursor::queryHash(['endpoint' => 'vocabulary'] + $query);
            FeatureCollection::assertCursor($paging->cursor, $hash, Vocabulary::SORT_KEYS[$sort->key][1]);

            RecordQuery::prepareFuzzy($filters);
            $records = Vocabulary::records($filters, $field);
            $result = Vocabulary::page($filters, $field, $sort, $paging, $hash);

            $values = [];
            foreach ($result['rows'] as $row) {
                $values[] = VocabularySerialiser::entry($row);
            }
            $total = $records['keys'];
            unset($records['keys']);

            return Json::response([
                'query' => $query,
                'total' => $total,
                'records' => $records,
                'page' => [
                    'returned' => count($values),
                    'per_page' => $result['per_page'],
                    'next_cursor' => $result['next_cursor'],
                    'next' => Paging::nextUrl($request, $result['next_cursor']),
                ],
                'values' => $values,
            ]);
        });
    }
}
