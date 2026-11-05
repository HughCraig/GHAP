<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use TLCMap\Api\V2\Params\Cursor;
use TLCMap\Api\V2\Params\Paging;
use TLCMap\Api\V2\Params\Sort;

/**
 * Keyset paging over any query (spec §5.3–5.5). The order is total: nulls placed by
 * `nulls`, then the key, then the id, both in the sort direction. A cursor holds the last
 * row's key and id, so each page continues strictly after it, and its values are bound.
 */
class KeysetPager
{
    /**
     * @param Builder $query   filters and select already applied; it is not modified
     * @param string  $keyExpr trusted SQL for the sort key (never user input)
     * @param string  $keyType a Cursor::KEY_TYPES value describing $keyExpr
     * @param string  $idExpr  trusted SQL for the unique row id
     * @return array{rows: array, next_cursor: ?string, per_page: int}
     */
    public static function page(
        Builder $query,
        string $keyExpr,
        string $keyType,
        Sort $sort,
        Paging $paging,
        string $hash,
        string $idExpr
    ): array {
        $after = $paging->cursor === null ? null : Cursor::decode($paging->cursor, $hash, $keyType);
        $served = $after === null ? 0 : $after->served;

        $n = $paging->limit === null ? $paging->perPage : min($paging->perPage, $paging->limit - $served);
        if ($n <= 0) {
            return ['rows' => [], 'next_cursor' => null, 'per_page' => $paging->perPage];
        }

        $key = "($keyExpr)";
        $id = "($idExpr)";
        $dir = $sort->direction === 'desc' ? 'DESC' : 'ASC';

        $q = self::isolate($query);
        if (empty($q->columns)) {
            $q->select('*');
        }
        $q->addSelect(new Expression("$keyExpr as _k"), new Expression("$idExpr as _id"));

        if ($after !== null) {
            [$sql, $bindings] = self::predicate($key, $id, $sort, $after);
            $q->whereRaw($sql, $bindings);
        }

        $q->orderByRaw(
            "$key IS NULL " . ($sort->nulls === 'last' ? 'ASC' : 'DESC') . ", $key $dir, $id $dir"
        );
        $q->limit($n + 1);

        $rows = $q->get()->all();

        $hasMore = count($rows) > $n && ($paging->limit === null || $served + $n < $paging->limit);
        $rows = array_slice($rows, 0, $n);

        $next = null;
        if ($hasMore) {
            $last = end($rows);
            $count = count($rows);
            $next = Cursor::encode(
                $hash,
                self::typedKey($last->_k, $keyType),
                self::typedInt($last->_id, 'id'),
                $served > PHP_INT_MAX - $count ? PHP_INT_MAX : $served + $count
            );
        }

        return ['rows' => $rows, 'next_cursor' => $next, 'per_page' => $paging->perPage];
    }

    /**
     * A copy of the query whose own conditions are grouped in parentheses, so a top-level
     * OR cannot absorb the keyset predicate, and whose order the pager owns.
     */
    private static function isolate(Builder $query): Builder
    {
        $q = clone $query;

        if ($q->wheres) {
            $nested = $q->forNestedWhere();
            $nested->wheres = $q->wheres;
            $nested->bindings['where'] = $q->bindings['where'];
            $q->wheres = [];
            $q->bindings['where'] = [];
            $q->addNestedWhereQuery($nested);
        }

        $q->orders = null;
        $q->bindings['order'] = [];

        return $q;
    }

    /** The condition for rows strictly after the cursor in this order; values are bound. */
    private static function predicate(string $key, string $id, Sort $sort, Cursor $after): array
    {
        $cmp = $sort->direction === 'desc' ? '<' : '>';

        if ($after->lastKey !== null) {
            $sql = "$key IS NOT NULL AND ($key, $id) $cmp (?, ?)";
            if ($sort->nulls === 'last') {
                $sql = "($sql) OR $key IS NULL";
            }
            return ["($sql)", [$after->lastKey, $after->lastId]];
        }

        $sql = "$key IS NULL AND $id $cmp ?";
        if ($sort->nulls === 'first') {
            $sql = "($sql) OR $key IS NOT NULL";
        }
        return ["($sql)", [$after->lastId]];
    }

    /** @return int|string|null */
    private static function typedKey($value, string $keyType)
    {
        if ($value === null) {
            if (strpos($keyType, 'nullable_') !== 0) {
                throw new \LogicException("The sort key of type '$keyType' returned null.");
            }
            return null;
        }
        if (str_replace('nullable_', '', $keyType) === 'int') {
            return self::typedInt($value, 'sort key');
        }
        if (!is_string($value)) {
            throw new \LogicException("The sort key of type '$keyType' did not return a string.");
        }
        return $value;
    }

    private static function typedInt($value, string $what): int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false || is_bool($value) || is_float($value)) {
            throw new \LogicException("The $what did not return an integer.");
        }
        return $int;
    }
}
