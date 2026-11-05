<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\Polygon;

/**
 * The visible records matching a RecordFilters (spec §6.2–6.4, §7), and the date expressions every
 * caller shares. The constants are trusted SQL over the alias `i` with no bindings, so they can
 * serve as sort keys.
 */
class RecordQuery
{
    /** First instant of datestart, or NULL. */
    public const S = 'tlcmap.v2_udate(i.datestart)';
    /** First instant of dateend, or NULL. */
    public const E = 'tlcmap.v2_udate(i.dateend)';
    /** Last instant of datestart, or NULL. */
    public const SE = 'tlcmap.v2_udate_end(i.datestart)';
    /** Last instant of dateend, or NULL. */
    public const EE = 'tlcmap.v2_udate_end(i.dateend)';

    /** The first instant the record covers, a missing side filled from the other (spec §6.2). */
    public const LO = 'least(coalesce(' . self::S . ', ' . self::E . '), coalesce(' . self::E . ', ' . self::S . '))';
    /** The last instant the record covers. */
    public const HI = 'greatest(coalesce(' . self::EE . ', ' . self::SE . '), coalesce(' . self::SE . ', ' . self::EE . '))';

    /** Sort key for `start`: the first instant of datestart, else of dateend. */
    public const START_KEY = 'coalesce(' . self::S . ', ' . self::E . ')';
    /** Sort key for `end`: the first instant of dateend, else of datestart. */
    public const END_KEY = 'coalesce(' . self::E . ', ' . self::S . ')';

    private const START_BLANK = "nullif(btrim(i.datestart), '') IS NULL";
    private const END_BLANK = "nullif(btrim(i.dateend), '') IS NULL";

    /** Both datestart and dateend are null or blank (spec §6.3). */
    public const NO_DATE = '(' . self::START_BLANK . ' AND ' . self::END_BLANK . ')';
    /** Either side has text that does not parse, whether or not the other side does. */
    public const UNPARSEABLE_SIDE = '((NOT ' . self::START_BLANK . ' AND ' . self::S . ' IS NULL)'
        . ' OR (NOT ' . self::END_BLANK . ' AND ' . self::E . ' IS NULL))';

    /** A record's extended-data field by name; the name is bound. */
    private const FIELD = 'tlcmap.v2_extended_value(i.extended_data, CAST(? AS text))';

    /**
     * Backslash as the LIKE escape character. PDO reads a backslash in a string literal as escaping
     * the next character, so a plain '\' would swallow the placeholders after it; E'\\' is one
     * backslash to PostgreSQL and a closed string to PDO.
     */
    private const ESCAPE = 'ESCAPE E\'\\\\\'';

    /** A decimal number, as the request side accepts it. `{0,1}` rather than `?`, which would read as a placeholder. */
    private const DECIMAL = "'^-{0,1}[0-9]+([.][0-9]+){0,1}\$'";

    /** The visible records matching every given filter. Selects nothing; callers select. */
    public static function build(RecordFilters $f): Builder
    {
        $q = DB::table('tlcmap.dataitem as i')
            ->leftJoin('tlcmap.dataset as d', 'd.id', '=', 'i.dataset_id')
            ->leftJoin('tlcmap.recordtype as rt', 'rt.id', '=', 'i.recordtype_id')
            ->whereRaw('(i.dataset_id IS NULL OR d.public = true)');

        $q->whereIn('i.datasource_id', $f->sourceIds);

        if ($f->name !== null) {
            self::name($q, $f->name, $f->match);
        }

        if ($f->layers !== null) {
            $q->whereIn('i.dataset_id', $f->layers);
        }

        if ($f->bbox !== null) {
            $b = $f->bbox;
            $envelope = 'i.geom && ST_MakeEnvelope(CAST(? AS double precision), CAST(? AS double precision), '
                . 'CAST(? AS double precision), CAST(? AS double precision), 4326)';
            if ($b->crossesAntimeridian()) {
                $q->whereRaw("($envelope OR $envelope)", self::floats([
                    $b->minLon, $b->minLat, 180.0, $b->maxLat,
                    -180.0, $b->minLat, $b->maxLon, $b->maxLat,
                ]));
            } else {
                $q->whereRaw($envelope, self::floats($b->toArray()));
            }
        }

        if ($f->polygon !== null) {
            list($polygon, $bindings) = self::polygonSql($f->polygon);
            $q->whereRaw("ST_Intersects(i.geom, $polygon)", $bindings);
        }

        if ($f->dateFrom !== null || $f->dateTo !== null) {
            $conditions = [];
            $bindings = [];
            if ($f->dateTo !== null) {
                $conditions[] = self::LO . ' <= CAST(? AS bigint)';
                $bindings[] = $f->dateTo->end;
            }
            if ($f->dateFrom !== null) {
                $conditions[] = self::HI . ' >= CAST(? AS bigint)';
                $bindings[] = $f->dateFrom->start;
            }
            $dated = implode(' AND ', $conditions);
            // A record is undated exactly when LO is null; the dated conditions are null for it.
            $q->whereRaw($f->includeUndated ? '(' . self::LO . " IS NULL OR ($dated))" : "($dated)", $bindings);
        }

        foreach (['i.state' => $f->state, 'i.lga' => $f->lga, 'i.feature_term' => $f->featureTerm,
                     'rt.type' => $f->recordType] as $column => $value) {
            if ($value !== null) {
                $q->whereRaw("lower($column) = lower(CAST(? AS text))", [$value]);
            }
        }

        if ($f->extendedData !== null) {
            foreach ($f->extendedData->clauses() as $clause) {
                self::extendedClause($q, $clause);
            }
        }

        return $q;
    }

    /**
     * Sets the trigram similarity threshold for this transaction when the match is fuzzy, so the
     * `%` operator applies v2's threshold whatever the database default. Callers run it, and the
     * queries it serves, inside DB::transaction.
     */
    public static function prepareFuzzy(RecordFilters $f): void
    {
        if ($f->match === 'fuzzy') {
            DB::select(
                "select set_config('pg_trgm.similarity_threshold', ?, true)",
                [(string) config('api_v2.fuzzy_threshold')]
            );
        }
    }

    /** Rejects a polygon PostGIS does not consider valid, with its reason. One query. */
    public static function assertValidPolygon(Polygon $polygon): void
    {
        list($sql, $bindings) = self::polygonSql($polygon);
        $row = DB::selectOne(
            "select ST_IsValid(g) as valid, ST_IsValidReason(g) as reason from (select $sql as g) as p",
            $bindings
        );
        if ($row->valid !== true) {
            throw ApiException::invalidGeometry('polygon', (string) $row->reason);
        }
    }

    /** @return array{0: string, 1: array} SQL for the polygon, built from bound vertices */
    private static function polygonSql(Polygon $polygon): array
    {
        $points = [];
        $bindings = [];
        foreach ($polygon->vertices() as $vertex) {
            $points[] = 'ST_MakePoint(CAST(? AS double precision), CAST(? AS double precision))';
            $bindings[] = self::float($vertex[0]);
            $bindings[] = self::float($vertex[1]);
        }
        $sql = 'ST_SetSRID(ST_MakePolygon(ST_MakeLine(ARRAY[' . implode(', ', $points) . '])), 4326)';
        return [$sql, $bindings];
    }

    /** A float as text that reads back exactly; a plain string cast would round to 14 digits. */
    private static function float(float $value): string
    {
        return var_export($value, true);
    }

    /** @return string[] */
    private static function floats(array $values): array
    {
        $out = [];
        foreach ($values as $value) {
            $out[] = self::float($value);
        }
        return $out;
    }

    /** `exact` and `contains` are ILIKE with the name escaped, so the trigram indexes serve all three modes. */
    private static function name(Builder $q, string $name, string $match): void
    {
        if ($match === 'fuzzy') {
            $q->whereRaw('(i.title % CAST(? AS text) OR i.placename % CAST(? AS text))', [$name, $name]);
            return;
        }
        $pattern = self::escapeLike($name);
        if ($match === 'contains') {
            $pattern = '%' . $pattern . '%';
        }
        $q->whereRaw(
            '(i.title ILIKE CAST(? AS text) ' . self::ESCAPE . ' OR i.placename ILIKE CAST(? AS text) ' . self::ESCAPE . ')',
            [$pattern, $pattern]
        );
    }

    private static function escapeLike(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
    }

    /**
     * One extended_data clause (spec §7.8). The field is read once; a field that is absent, empty,
     * malformed or of the wrong kind yields false or NULL, so the record does not match. Casts are
     * guarded by CASE, since PostgreSQL does not evaluate AND left to right.
     */
    private static function extendedClause(Builder $q, array $clause): void
    {
        $field = self::FIELD;
        switch ($clause['op']) {
            case '=':
                $q->whereRaw("$field = CAST(? AS text)", [$clause['field'], $clause['value']]);
                break;
            case 'textmatch':
                $q->whereRaw(
                    "$field ILIKE CAST(? AS text) " . self::ESCAPE,
                    [$clause['field'], '%' . self::escapeLike($clause['value']) . '%']
                );
                break;
            case '>':
            case '<':
                $op = $clause['op'];
                $q->whereRaw(
                    '(SELECT CASE WHEN x.v ~ ' . self::DECIMAL . " THEN x.v::numeric $op CAST(? AS numeric) ELSE false END"
                    . " FROM (SELECT btrim($field, E' \\t\\r\\n') AS v) AS x)",
                    [$clause['value'], $clause['field']]
                );
                break;
            case 'before':
                $q->whereRaw("tlcmap.v2_udate_end($field) < CAST(? AS bigint)", [$clause['field'], $clause['date']->start]);
                break;
            case 'after':
                $q->whereRaw("tlcmap.v2_udate($field) > CAST(? AS bigint)", [$clause['field'], $clause['date']->end]);
                break;
            default:
                throw new \LogicException('Unknown extended_data operator ' . $clause['op']);
        }
    }
}
