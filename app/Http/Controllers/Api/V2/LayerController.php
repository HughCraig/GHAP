<?php

namespace TLCMap\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Params\Cursor;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Sort;
use TLCMap\Api\V2\Query\KeysetPager;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\RecordQuery;
use TLCMap\Api\V2\Query\Summary;
use TLCMap\Api\V2\Serialise\FeatureCollection;
use TLCMap\Api\V2\Serialise\FeatureSerialiser;
use TLCMap\Api\V2\Serialise\LayerSerialiser;
use TLCMap\Api\V2\Support\Json;
use TLCMap\Http\Controllers\Controller;

/** GET /api/v2/layers/{id} and /features — one layer (spec §10.3, §10.4). */
class LayerController extends Controller
{
    public const FEATURES_ALLOWED = ['sort', 'direction', 'nulls', 'limit', 'cursor', 'per_page'];

    /** Sort name => [key expression, cursor key type]; trusted SQL with no bindings. */
    public const SORT_KEYS = [
        'order' => ['i.dataset_order', 'nullable_int'],
        'id' => ['i.id', 'int'],
        'title' => ['i.title', 'string'],
        'start' => [RecordQuery::START_KEY, 'nullable_int'],
        'end' => [RecordQuery::END_KEY, 'nullable_int'],
    ];

    private const ID = 'i.id';

    public function show(Request $request, int $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            ParameterSet::fromRequest($request, []);
            $filters = RecordFilters::forLayer($id);

            return Json::response([
                'layer' => self::layer($id),
                'summary' => Summary::compute(RecordQuery::build($filters), false),
            ]);
        });
    }

    public function features(Request $request, int $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $p = ParameterSet::fromRequest($request, self::FEATURES_ALLOWED);
            $filters = RecordFilters::forLayer($id);
            $sort = Sort::parse($p, array_keys(self::SORT_KEYS), 'order');
            $paging = Paging::parse(
                $p,
                (int) config('api_v2.features_per_page'),
                (int) config('api_v2.features_per_page_max')
            );

            $query = $sort->toEcho();
            $query['limit'] = $paging->limit;
            $query['cursor'] = $paging->cursor;
            $query['per_page'] = $paging->perPage;
            $hash = Cursor::queryHash(['layer' => $id] + $query);
            list($keyExpr, $keyType) = self::SORT_KEYS[$sort->key];
            FeatureCollection::assertCursor($paging->cursor, $hash, $keyType);

            $summary = Summary::compute(RecordQuery::build($filters), false);
            $result = KeysetPager::page(
                RecordQuery::build($filters)->select(FeatureSerialiser::columns()),
                $keyExpr,
                $keyType,
                $sort,
                $paging,
                $hash,
                self::ID
            );
            $collection = FeatureCollection::build($request, $result);

            return Json::response([
                'type' => 'FeatureCollection',
                'layer' => self::layer($id),
                'query' => $query,
                'summary' => $summary,
                'page' => $collection['page'],
                'features' => $collection['features'],
            ]);
        });
    }

    /** The layer object; the gate has already shown the layer exists. */
    private static function layer(int $id): array
    {
        $dataset = DB::table('tlcmap.dataset')->where('id', $id)->first();
        $keywords = DB::table('tlcmap.dataset_subject_keyword as dk')
            ->join('tlcmap.subject_keyword as k', 'k.id', '=', 'dk.subject_keyword_id')
            ->where('dk.dataset_id', $id)
            ->orderBy('k.keyword')
            ->pluck('k.keyword')
            ->all();
        $type = $dataset->recordtype_id === null
            ? null
            : DB::table('tlcmap.recordtype')->where('id', $dataset->recordtype_id)->value('type');

        return LayerSerialiser::layer($dataset, $keywords, $type);
    }
}
