<?php

namespace Tests\V2\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\Cursor;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\ParameterSet;
use TLCMap\Api\V2\Params\Sort;
use TLCMap\Api\V2\Query\KeysetPager;

class KeysetPagerTest extends TestCase
{
    private const ALLOWED = ['sort', 'direction', 'nulls', 'per_page', 'limit', 'cursor'];

    /** Sort name => [key expression, key type]. */
    private const KEYS = [
        'id' => ['i.id', 'int'],
        'title' => ['i.title', 'string'],
        'order' => ['i.dataset_order', 'nullable_int'],
        'placename' => ['i.placename', 'nullable_string'],
    ];

    /**
     * 23 records, inserted out of order. Titles include case variants and three identical
     * 'Beta'; dataset_order and placename include nulls and repeated values.
     */
    private const ROWS = [
        ['Beta', 7, null],
        ['Lismore', 3, 'm'],
        ['ALPHA', null, 'b'],
        ['Dubbo', 12, null],
        ['alpha', 7, 'b'],
        ['Moree', null, 'z'],
        ['Acacia', 1, null],
        ['Beta', 3, 'a'],
        ['Gosford', 20, 'b'],
        ["O'Connell", null, null],
        ['Bega', 7, 'c'],
        ['Aardvark', 15, 'a'],
        ['Alpha', 2, null],
        ['Kempsey', null, 'b'],
        ['Bathurst', 9, 'q'],
        ['Beta', 12, null],
        ['Cessnock', 5, 'b'],
        ['Banksia', null, 'c'],
        ['Bombala', 3, 'a'],
        ['Inverell', 18, 'b'],
        ['Barrington', 7, null],
        ['Bellingen', 1, 'm'],
        ['Maitland', 4, 'b'],
    ];

    /** @var int[] */
    private $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (self::ROWS as [$title, $order, $placename]) {
            $this->ids[] = Fixtures::record([
                'title' => $title,
                'dataset_order' => $order,
                'placename' => $placename,
            ]);
        }
    }

    private function base()
    {
        return DB::table('tlcmap.dataitem as i')
            ->whereBetween('i.id', [800000, 999999])
            ->select('i.id', 'i.title', 'i.dataset_order', 'i.placename');
    }

    private function params(array $params): ParameterSet
    {
        return ParameterSet::fromRequest(Request::create('/api/v2/test?' . http_build_query($params)), self::ALLOWED);
    }

    /** One page through the pager, with the hash computed from the request as an endpoint would. */
    private function page(array $params, $base = null, ?string $hash = null): array
    {
        $p = $this->params($params);
        $sort = Sort::parse($p, array_keys(self::KEYS), 'id');
        $paging = Paging::parse($p, 500, 5000);
        [$expr, $type] = self::KEYS[$sort->key];

        return KeysetPager::page(
            $base ?: $this->base(),
            $expr,
            $type,
            $sort,
            $paging,
            $hash ?? Cursor::queryHash($p->all()),
            'i.id'
        );
    }

    private static function ids(array $rows): array
    {
        return array_map(function ($row) {
            return (int) $row->id;
        }, $rows);
    }

    /** Follow next_cursor to the end; returns the list of pages of ids. */
    private function traverse(array $params, $base = null): array
    {
        $pages = [];
        $cursor = null;
        do {
            $query = $params;
            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }
            $result = $this->page($query, $base);
            $pages[] = self::ids($result['rows']);
            $cursor = $result['next_cursor'];
            $this->assertLessThan(100, count($pages), 'Paging did not terminate.');
        } while ($cursor !== null);

        return $pages;
    }

    /** The oracle: one unpaged query using PostgreSQL's own NULLS FIRST/LAST, id breaking ties. */
    private function unpaged(string $key, string $direction, string $nulls): array
    {
        [$expr] = self::KEYS[$key];
        $dir = strtoupper($direction);
        $rows = $this->base()
            ->orderByRaw("$expr $dir NULLS " . strtoupper($nulls) . ", i.id $dir")
            ->get()
            ->all();

        return array_map(function ($row) use ($expr) {
            $column = substr($expr, 2);
            return ['id' => (int) $row->id, 'key' => $row->$column];
        }, $rows);
    }

    /** Whether the given positions (in the full order) fall on more than one page. */
    private static function straddles(array $positions, int $perPage): bool
    {
        $pages = array_unique(array_map(function ($p) use ($perPage) {
            return intdiv($p, $perPage);
        }, $positions));

        return count($pages) > 1;
    }

    public function testFollowingCursorsVisitsEveryRowOnceInTheUnpagedOrder(): void
    {
        $sortedIds = $this->ids;
        sort($sortedIds);

        foreach (array_keys(self::KEYS) as $key) {
            foreach (['asc', 'desc'] as $direction) {
                foreach (['first', 'last'] as $nulls) {
                    $label = "sort=$key direction=$direction nulls=$nulls";
                    $expected = $this->unpaged($key, $direction, $nulls);

                    $pages = $this->traverse(['sort' => $key, 'direction' => $direction, 'nulls' => $nulls, 'per_page' => 4]);

                    $this->assertSame([4, 4, 4, 4, 4, 3], array_map('count', $pages), $label);
                    $served = array_merge(...$pages);
                    $this->assertSame(array_column($expected, 'id'), $served, $label);

                    $unique = array_values(array_unique($served));
                    sort($unique);
                    $this->assertSame($sortedIds, $unique, $label);

                    $this->assertFixtureStraddlesPages($key, $expected, $label);
                }
            }
        }
    }

    /**
     * The fixture is only a good test if equal and case-variant keys, and the null group, sit
     * across page boundaries in every order; this guards against the fixture drifting.
     */
    private function assertFixtureStraddlesPages(string $key, array $expected, string $label): void
    {
        if ($key === 'id') {
            return;
        }

        $groups = [];
        foreach ($expected as $position => $row) {
            $k = $row['key'];
            if ($key === 'title' && strtolower($k) === 'alpha') {
                $k = 'alpha-case-variants';
            }
            $groups[$k === null ? "\0null" : (string) $k][] = $position;
        }

        if ($key === 'title') {
            $this->assertCount(3, $groups['alpha-case-variants'], $label);
            $this->assertTrue(self::straddles($groups['alpha-case-variants'], 4), "$label: case variants");
            $this->assertCount(3, $groups['Beta'], $label);
            $this->assertTrue(self::straddles($groups['Beta'], 4), "$label: identical titles");
            return;
        }

        $this->assertTrue(self::straddles($groups["\0null"], 4), "$label: null group");
        unset($groups["\0null"]);
        $straddling = array_filter($groups, function ($positions) {
            return count($positions) > 1 && self::straddles($positions, 4);
        });
        $this->assertNotEmpty($straddling, "$label: a group of equal keys");
    }

    public function testLimitCapsTheResultAcrossPages(): void
    {
        $expected = array_column($this->unpaged('title', 'asc', 'last'), 'id');

        $pages = $this->traverse(['sort' => 'title', 'limit' => 10, 'per_page' => 4]);
        $this->assertSame([4, 4, 2], array_map('count', $pages));
        $this->assertSame(array_slice($expected, 0, 10), array_merge(...$pages));

        $pages = $this->traverse(['sort' => 'title', 'limit' => 8, 'per_page' => 4]);
        $this->assertSame([4, 4], array_map('count', $pages));

        $pages = $this->traverse(['sort' => 'title', 'limit' => 3, 'per_page' => 4]);
        $this->assertSame([3], array_map('count', $pages));

        $pages = $this->traverse(['sort' => 'title', 'limit' => 100, 'per_page' => 10]);
        $this->assertSame([10, 10, 3], array_map('count', $pages));
        $this->assertSame($expected, array_merge(...$pages));

        $result = $this->page(['sort' => 'title', 'limit' => 10, 'per_page' => 4]);
        $this->assertSame(4, $result['per_page']);
    }

    public function testPerPageEqualToTheMatchCountGivesOnePageWithoutCursor(): void
    {
        $result = $this->page(['per_page' => 23]);
        $this->assertCount(23, $result['rows']);
        $this->assertNull($result['next_cursor']);
        $this->assertSame(23, $result['per_page']);

        $result = $this->page(['per_page' => 22]);
        $this->assertCount(22, $result['rows']);
        $this->assertNotNull($result['next_cursor']);
    }

    public function testPerPageMayChangeBetweenPages(): void
    {
        $expected = array_column($this->unpaged('order', 'desc', 'first'), 'id');
        $params = ['sort' => 'order', 'direction' => 'desc', 'nulls' => 'first'];

        $first = $this->page($params + ['per_page' => 4]);
        $second = $this->page($params + ['per_page' => 10, 'cursor' => $first['next_cursor']]);
        $third = $this->page($params + ['per_page' => 100, 'cursor' => $second['next_cursor']]);

        $this->assertSame($expected, array_merge(self::ids($first['rows']), self::ids($second['rows']), self::ids($third['rows'])));
        $this->assertNull($third['next_cursor']);
    }

    public function testDeletingTheLastServedRowContinuesWithTheFollowingRow(): void
    {
        foreach (['title', 'order', 'placename'] as $key) {
            DB::beginTransaction();
            $params = ['sort' => $key, 'per_page' => 4];
            $expected = array_column($this->unpaged($key, 'asc', 'last'), 'id');

            $first = $this->page($params);
            $firstIds = self::ids($first['rows']);
            $this->assertSame(array_slice($expected, 0, 4), $firstIds, $key);

            DB::table('tlcmap.dataitem')->where('id', end($firstIds))->delete();

            $rest = [];
            $cursor = $first['next_cursor'];
            while ($cursor !== null) {
                $page = $this->page($params + ['cursor' => $cursor]);
                $rest = array_merge($rest, self::ids($page['rows']));
                $cursor = $page['next_cursor'];
            }
            $this->assertSame(array_slice($expected, 4), $rest, $key);
            DB::rollBack();
        }
    }

    public function testInsertingARowBeforeTheCursorDoesNotServeItOrChangeAnythingElse(): void
    {
        $cases = [
            'title' => [['title' => 'Aaa'], ['id' => 899999, 'title' => 'Alpha']],
            'order' => [['dataset_order' => 0], ['id' => 899999, 'dataset_order' => 2]],
        ];
        foreach ($cases as $key => $inserts) {
            DB::beginTransaction();
            $params = ['sort' => $key, 'per_page' => 4];
            $expected = array_column($this->unpaged($key, 'asc', 'last'), 'id');

            $first = $this->page($params);
            $this->assertSame(array_slice($expected, 0, 4), self::ids($first['rows']), $key);

            $inserted = [];
            foreach ($inserts as $attrs) {
                $inserted[] = Fixtures::record($attrs);
            }
            // Both inserted rows now sort before the cursor position.
            $now = array_column($this->unpaged($key, 'asc', 'last'), 'id');
            $this->assertLessThan(5, array_search($inserted[0], $now, true), $key);
            $this->assertLessThan(5, array_search($inserted[1], $now, true), $key);

            $rest = [];
            $cursor = $first['next_cursor'];
            while ($cursor !== null) {
                $page = $this->page($params + ['cursor' => $cursor]);
                $rest = array_merge($rest, self::ids($page['rows']));
                $cursor = $page['next_cursor'];
            }
            $this->assertSame(array_slice($expected, 4), $rest, $key);
            DB::rollBack();
        }
    }

    public function testACursorFromAnotherQueryIsRejected(): void
    {
        $first = $this->page(['sort' => 'title', 'per_page' => 4]);

        try {
            $this->page(['sort' => 'id', 'per_page' => 4, 'cursor' => $first['next_cursor']]);
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('INVALID_PARAMETER', $e->errorCode());
            $this->assertSame('cursor', $e->details()['parameter']);
        }

        try {
            $this->page(['sort' => 'title', 'limit' => 50, 'per_page' => 4, 'cursor' => $first['next_cursor']]);
            $this->fail('Expected ApiException');
        } catch (ApiException $e) {
            $this->assertSame('cursor', $e->details()['parameter']);
        }
    }

    public function testCursorValuesAreBoundNeverInterpolated(): void
    {
        $evil = "B') OR 1=1; DROP TABLE tlcmap.dataitem; --";
        $hash = Cursor::queryHash(['sort' => 'title']);
        $cursor = Cursor::encode($hash, $evil, 900001, 0);

        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query;
        });

        $result = $this->page(['sort' => 'title', 'cursor' => $cursor], null, $hash);

        $this->assertCount(1, $queries);
        $this->assertStringNotContainsString('DROP TABLE', $queries[0]->sql);
        $this->assertContains($evil, $queries[0]->bindings);

        $expected = array_values(array_filter($this->unpaged('title', 'asc', 'last'), function ($row) use ($evil) {
            return DB::selectOne('select (?::text, ?::bigint) < (?::text, ?::bigint) as after', [$evil, 900001, $row['key'], $row['id']])->after;
        }));
        $this->assertSame(array_column($expected, 'id'), self::ids($result['rows']));
        $this->assertNotEmpty($result['rows']);
    }

    public function testCallerOrConditionsStayGroupedAndTheBuilderIsNotModified(): void
    {
        $base = $this->base()->where(function ($q) {
            $q->where('i.id', '<', 900012)->orWhere('i.id', '>=', 900012);
        });
        $loose = DB::table('tlcmap.dataitem as i')
            ->select('i.id', 'i.title')
            ->where('i.id', '<', 900012)
            ->orWhere('i.id', '>=', 900012);
        $sql = $loose->toSql();
        $bindings = $loose->getBindings();

        $expected = array_column($this->unpaged('title', 'desc', 'last'), 'id');
        $this->assertSame($expected, array_merge(...$this->traverse(['sort' => 'title', 'direction' => 'desc', 'per_page' => 4], $base)));
        $this->assertSame($expected, array_merge(...$this->traverse(['sort' => 'title', 'direction' => 'desc', 'per_page' => 4], $loose)));

        $this->assertSame($sql, $loose->toSql());
        $this->assertSame($bindings, $loose->getBindings());
    }

    public function testRowsCarryTheKeyAndId(): void
    {
        $result = $this->page(['sort' => 'order', 'per_page' => 2]);
        $row = $result['rows'][0];
        $this->assertSame((int) $row->id, (int) $row->_id);
        $this->assertSame($row->dataset_order, $row->_k);
    }
}
