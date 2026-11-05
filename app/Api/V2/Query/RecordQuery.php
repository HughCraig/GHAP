<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\ExtendedDataFilter;
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

    /** Null or blank, trimming the same whitespace as v2_udate (spaces, tabs, CR, LF). */
    public const START_BLANK = 'nullif(btrim(i.datestart, E\' \t\r\n\'), \'\') IS NULL';
    public const END_BLANK = 'nullif(btrim(i.dateend, E\' \t\r\n\'), \'\') IS NULL';

    /** Both datestart and dateend are null or blank (spec §6.3). */
    public const NO_DATE = '(' . self::START_BLANK . ' AND ' . self::END_BLANK . ')';
    /** Either side has text that does not parse, whether or not the other side does. */
    public const UNPARSEABLE_SIDE = '((NOT ' . self::START_BLANK . ' AND ' . self::S . ' IS NULL)'
        . ' OR (NOT ' . self::END_BLANK . ' AND ' . self::E . ' IS NULL))';

    /** Both bounds of datestart, {S, SE}, from one parse; NULL when it does not parse. */
    public const START_RANGE = 'tlcmap.v2_udate_range(i.datestart)';
    /** Both bounds of dateend, {E, EE}, from one parse. */
    public const END_RANGE = 'tlcmap.v2_udate_range(i.dateend)';

    /**
     * The two ranges of the row `i`, joined as the one-row lateral table `x` when a date filter
     * needs them, each computed once: OFFSET 0 keeps PostgreSQL from flattening it, which would
     * copy the calls into every use. A lateral join rather than a subquery in the condition, which
     * measured twice as slow.
     */
    private const X_RANGES = 'LATERAL (SELECT ' . self::START_RANGE . ' AS sr, ' . self::END_RANGE . ' AS er OFFSET 0) AS x';

    /** LO and HI over `x`; with X_COLUMNS substituted, each is its counterpart exactly (RecordQueryTest). */
    public const X_LO = 'least(coalesce(x.sr[1], x.er[1]), coalesce(x.er[1], x.sr[1]))';
    public const X_HI = 'greatest(coalesce(x.er[2], x.sr[2]), coalesce(x.sr[2], x.er[2]))';

    /** Each bound read from `x` and the expression it equals. */
    public const X_COLUMNS = ['x.sr[1]' => self::S, 'x.er[1]' => self::E, 'x.sr[2]' => self::SE, 'x.er[2]' => self::EE];

    /*
     * Each row's date values, computed once (see withDates): the ranges sr, er and the blank flags
     * sb, eb are START_RANGE, END_RANGE, START_BLANK and END_BLANK; s, e, se, ee are read from the
     * ranges, and equal S, E, SE, EE.
     */
    public const ROW_RANGE_COLUMNS = self::START_RANGE . ' as sr, ' . self::END_RANGE . ' as er, '
        . self::START_BLANK . ' as sb, ' . self::END_BLANK . ' as eb';
    /** The same, when the date filter has already joined the ranges as `x`. */
    public const ROW_RANGE_COLUMNS_FROM_X = 'x.sr as sr, x.er as er, '
        . self::START_BLANK . ' as sb, ' . self::END_BLANK . ' as eb';
    public const ROW_BOUND_COLUMNS = 'q.*, q.sr[1] as s, q.er[1] as e, q.sr[2] as se, q.er[2] as ee';

    /*
     * LO, HI, NO_DATE and UNPARSEABLE_SIDE over those columns of the derived table `r`. Each is its
     * counterpart above with every call replaced by its column; RecordQueryTest checks that the
     * substitution gives the counterpart exactly, so the two cannot drift.
     */
    public const R_LO = 'least(coalesce(r.s, r.e), coalesce(r.e, r.s))';
    public const R_HI = 'greatest(coalesce(r.ee, r.se), coalesce(r.se, r.ee))';
    public const R_NO_DATE = '(r.sb AND r.eb)';
    public const R_UNPARSEABLE_SIDE = '((NOT r.sb AND r.s IS NULL) OR (NOT r.eb AND r.e IS NULL))';

    /** Each column of `r` and the expression it holds. */
    public const R_COLUMNS = [
        'r.s' => self::S, 'r.e' => self::E, 'r.se' => self::SE, 'r.ee' => self::EE,
        'r.sb' => self::START_BLANK, 'r.eb' => self::END_BLANK,
    ];

    /** A record's extended-data field by name; the name is bound. */
    private const FIELD = 'tlcmap.v2_extended_value(i.extended_data, CAST(? AS text))';

    /*
     * Raw extended data whose Data names (or values) may not be written as v2_extended_value reads
     * them, apart from the characters a literal run never contains (see literalRun). A pre-filter
     * always lets such a record through, to be parsed.
     *
     * Parsing changes a Data name only by decoding references, by turning a tab, carriage return or
     * line feed into a space, and by what a DTD can declare (entities, default attributes). Without
     * a character reference (`&#`) or a DTD (`<!DOCTYPE`), what remains is the five predefined
     * entities, which decode to & < > " ', and whitespace: a run of the name is in the raw text as
     * written.
     *
     * A value also passes through any markup inside its element. When, besides, every `<value`
     * opens `<value><![CDATA[` and every `]]>` is followed by `</value>`, the value element's content
     * is exactly one CDATA section, read as written but for line endings: a run of the value is in
     * the raw text as written. (Removing the longer marker cannot hide a shorter one that is not
     * part of it, and can only join text into a new one, which errs towards parsing.)
     *
     * No record in temp_tlcmap matches either (2026-11-05). They are LIKEs, so the planner estimates
     * them from the column's statistics, or as rare, rather than as near-certain; the partial index
     * v2_dataitem_extended_data_unliteral has exactly VALUES_NOT_AS_WRITTEN's terms as its
     * condition, so each term can be answered from it.
     */
    private const NAMES_NOT_AS_WRITTEN = "i.extended_data LIKE '%&#%' OR i.extended_data LIKE '%<!DOCTYPE%'";
    private const VALUES_NOT_AS_WRITTEN = self::NAMES_NOT_AS_WRITTEN
        . " OR replace(i.extended_data, '<value><![CDATA[', '') LIKE '%<value%'"
        . " OR replace(i.extended_data, ']]></value>', '') LIKE '%]]>%'";

    /**
     * Backslash as the LIKE escape character. PDO reads a backslash in a string literal as escaping
     * the next character, so a plain '\' would swallow the placeholders after it; E'\\' is one
     * backslash to PostgreSQL and a closed string to PDO.
     */
    public const ESCAPE = 'ESCAPE E\'\\\\\'';

    /**
     * Whether x.v is a decimal number as the request side accepts it, with at most
     * ExtendedDataFilter::MAX_DIGITS digits either side of the point: numeric cannot hold every
     * longer value, and one it cannot hold would fail the query. A longer value does not match, as
     * a value that is not a number does not. PostgreSQL bounds repetition at 255, so the digits are
     * counted by length. `{0,1}` rather than `?`, which would read as a placeholder.
     */
    private const DECIMAL = "x.v ~ '^-{0,1}[0-9]+([.][0-9]+){0,1}\$'"
        . " AND length(ltrim(split_part(x.v, '.', 1), '-')) <= " . ExtendedDataFilter::MAX_DIGITS
        . " AND length(split_part(x.v, '.', 2)) <= " . ExtendedDataFilter::MAX_DIGITS;

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
                $conditions[] = self::X_LO . ' <= CAST(? AS bigint)';
                $bindings[] = $f->dateTo->end;
            }
            if ($f->dateFrom !== null) {
                $conditions[] = self::X_HI . ' >= CAST(? AS bigint)';
                $bindings[] = $f->dateFrom->start;
            }
            $dated = implode(' AND ', $conditions);
            // A record is undated exactly when LO is null; the dated conditions are null for it.
            // Each side is parsed once, for both its bounds.
            $q->crossJoin(DB::raw(self::X_RANGES));
            $q->whereRaw($f->includeUndated ? '(' . self::X_LO . " IS NULL OR ($dated))" : "($dated)", $bindings);
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
     * The rows of $records as the derived table `r`: the given columns plus ROW_RANGE_COLUMNS and
     * ROW_BOUND_COLUMNS, so an aggregate over R_LO, R_HI and the rest parses each date once per row
     * rather than once per bound and use. OFFSET 0 keeps PostgreSQL from flattening the inner
     * subquery, which would copy the calls back into every expression that reads them. When a date
     * filter has joined the ranges, they are read from it rather than parsed again.
     *
     * @param Builder  $records an unselected RecordQuery builder; cloned, never mutated
     * @param string[] $columns trusted column expressions over `i`
     */
    public static function withDates(Builder $records, array $columns): Builder
    {
        $ranges = self::ROW_RANGE_COLUMNS;
        foreach ((array) $records->joins as $join) {
            if ($join->table instanceof Expression && $join->table->getValue() === self::X_RANGES) {
                $ranges = self::ROW_RANGE_COLUMNS_FROM_X;
            }
        }
        $rows = (clone $records)->select($columns)->selectRaw($ranges)->offset(0);
        $bounds = DB::query()->fromSub($rows, 'q')->selectRaw(self::ROW_BOUND_COLUMNS);

        return DB::query()->fromSub($bounds, 'r');
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

    /**
     * The longest run of $operand with no whitespace and none of & < > " ' (the first, if several
     * are as long), or '' when there is none. In raw text that NAMES_NOT_AS_WRITTEN (for a name) or
     * VALUES_NOT_AS_WRITTEN (for a value) does not match, parsing changes only those characters, so
     * text that reads as $operand once parsed contains its run as written.
     */
    public static function literalRun(string $operand): string
    {
        $longest = '';
        foreach (preg_split('/[\s&<>"\']+/u', $operand) as $run) {
            if (strlen($run) > strlen($longest)) {
                $longest = $run;
            }
        }
        return $longest;
    }

    /**
     * Necessary conditions on the raw extended data for a clause to match, as [sql, bindings] each:
     * it contains the field name's literal run, and for `=` and `textmatch` the value's, compared
     * as the clause compares (`=` exactly, `textmatch` ignoring case: ILIKE lower-cases character by
     * character, so the lower-cased run lies in the lower-cased raw text wherever the lower-cased
     * value lies in the lower-cased field). A record whose text may not be written as it reads
     * always passes, and the partial index v2_dataitem_extended_data_unliteral finds those without
     * reading every record. They spare the XML parse of a record that cannot match and never
     * exclude one that can; a `>`, `<`, `before` or `after` value is not looked for, since the text
     * of a matching value is unknown.
     *
     * @param array $clause one of ExtendedDataFilter::clauses()
     * @return array[]
     */
    public static function prefilter(array $clause): array
    {
        $conditions = [];
        $name = self::literalRun($clause['field']);
        if ($name !== '') {
            $conditions[] = [
                '(i.extended_data LIKE CAST(? AS text) ' . self::ESCAPE . ' OR ' . self::NAMES_NOT_AS_WRITTEN . ')',
                ['%' . self::escapeLike($name) . '%'],
            ];
        }
        if ($clause['op'] === '=' || $clause['op'] === 'textmatch') {
            $value = self::literalRun($clause['value']);
            if ($value !== '') {
                $like = $clause['op'] === '=' ? 'LIKE' : 'ILIKE';
                $conditions[] = [
                    "(i.extended_data $like CAST(? AS text) " . self::ESCAPE . ' OR ' . self::VALUES_NOT_AS_WRITTEN . ')',
                    ['%' . self::escapeLike($value) . '%'],
                ];
            }
        }
        return $conditions;
    }

    /** A value with LIKE's `\`, `%` and `_` escaped, for use with ESCAPE. */
    public static function escapeLike(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
    }

    /**
     * One extended_data clause (spec §7.8), after its pre-filters. The field is read once (for `>`
     * and `<`, OFFSET 0 keeps the derived value from being copied into each use); a field that is
     * absent, empty, malformed or of the wrong kind yields false or NULL, so the record does not
     * match. Casts are guarded by CASE, since PostgreSQL does not evaluate AND left to right.
     */
    private static function extendedClause(Builder $q, array $clause): void
    {
        foreach (self::prefilter($clause) as list($sql, $bindings)) {
            $q->whereRaw($sql, $bindings);
        }

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
                    '(SELECT CASE WHEN ' . self::DECIMAL . " THEN x.v::numeric $op CAST(? AS numeric) ELSE false END"
                    . " FROM (SELECT btrim($field, E' \\t\\r\\n') AS v OFFSET 0) AS x)",
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
