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
use TLCMap\Api\V2\Query\KeysetPager;
use TLCMap\Api\V2\Query\LayerCatalogue;
use TLCMap\Api\V2\Query\LayerStatistics;
use TLCMap\Api\V2\Query\RecordFilters;
use TLCMap\Api\V2\Query\RecordQuery;
use TLCMap\Api\V2\Query\Summary;
use TLCMap\Api\V2\Query\Vocabulary;
use TLCMap\Api\V2\Serialise\FeatureCollection;
use TLCMap\Api\V2\Serialise\FeatureSerialiser;
use TLCMap\Api\V2\Serialise\LayerSerialiser;
use TLCMap\Api\V2\Serialise\StatisticsSerialiser;
use TLCMap\Api\V2\Serialise\VocabularySerialiser;
use TLCMap\Api\V2\Support\Json;
use TLCMap\Http\Controllers\Controller;

/** GET /api/v2/layers — the catalogue — and /layers/{id} and /features — one layer (spec §10.2–10.4). */
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

    /** GET /api/v2/layers — the catalogue (spec §10.2). */
    public function index(Request $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $p = ParameterSet::fromRequest($request, LayerCatalogue::ALLOWED);
            $records = RecordFilters::fromParams($p, LayerCatalogue::RECORD_LEVEL);
            $layerFilters = [
                'q' => $p->string('q'),
                'record_type' => $p->string('record_type'),
                'creator' => $p->string('creator'),
                'has_license' => $p->has('has_license') ? $p->bool('has_license', false) : null,
                'has_warning' => $p->has('has_warning') ? $p->bool('has_warning', false) : null,
                'min_records' => $p->int('min_records', 0, PHP_INT_MAX, null),
            ];
            $sort = Sort::parse($p, array_keys(LayerCatalogue::SORT_KEYS), LayerCatalogue::DEFAULT_SORT);
            $paging = Paging::parse(
                $p,
                (int) config('api_v2.layers_per_page'),
                (int) config('api_v2.layers_per_page_max')
            );

            $query = self::catalogueQuery($records, $layerFilters, $sort, $paging);
            $hash = Cursor::queryHash(['endpoint' => 'layers'] + $query);
            FeatureCollection::assertCursor($paging->cursor, $hash, LayerCatalogue::SORT_KEYS[$sort->key][1]);

            $result = LayerCatalogue::run($layerFilters, $records, $sort, $paging, $hash);
            $withMatching = $records->hasRecordLevelFilter();
            $layers = [];
            foreach ($result['rows'] as $row) {
                $layers[] = LayerSerialiser::catalogueEntry($row, $withMatching);
            }

            return Json::response([
                'query' => $query,
                'total' => $result['total'],
                'page' => [
                    'returned' => count($layers),
                    'per_page' => $result['per_page'],
                    'next_cursor' => $result['next_cursor'],
                    'next' => Paging::nextUrl($request, $result['next_cursor']),
                ],
                'layers' => $layers,
            ]);
        });
    }

    /** Every parameter of the catalogue with its effective value, null when not given (spec §5.2). */
    private static function catalogueQuery(RecordFilters $records, array $layerFilters, Sort $sort, Paging $paging): array
    {
        $query = [];
        $recordEcho = $records->toEcho();
        foreach (LayerCatalogue::ALLOWED as $name) {
            if (in_array($name, LayerCatalogue::RECORD_LEVEL, true)) {
                $query[$name] = $recordEcho[$name];
            } elseif (array_key_exists($name, $layerFilters)) {
                $query[$name] = $layerFilters[$name];
            }
        }
        $query['sort'] = $sort->key;
        $query['direction'] = $sort->direction;
        $query['cursor'] = $paging->cursor;
        $query['per_page'] = $paging->perPage;

        return $query;
    }

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

    /** GET /api/v2/layers/{id}/statistics (A2 spec §6). */
    public function statistics(Request $request, int $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            ParameterSet::fromRequest($request, []);
            $filters = RecordFilters::forLayer($id);
            $row = LayerStatistics::compute($filters);
            $dataset = self::dataset($id);
            $field = VocabularyField::named('feature_term');
            $records = Vocabulary::records($filters, $field);
            $entries = Vocabulary::all($filters, $field);

            return Json::response([
                'layer' => LayerSerialiser::identity($dataset),
                'statistics' => StatisticsSerialiser::statistics($row, $records, $entries),
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

    private static function dataset(int $id): \stdClass
    {
        return DB::table('tlcmap.dataset')->where('id', $id)->first();
    }

    /** The layer object; the gate has already shown the layer exists. */
    private static function layer(int $id): array
    {
        $dataset = self::dataset($id);
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
