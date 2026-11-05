<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateV2DateFunctions extends Migration
{
    public function up()
    {
        // The single parse. is_end = false gives the first instant of the period named, true the last.
        // The ISO form is tried first, since most stored values are Y-MM-DD.
        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_udate_parts(input text, is_end boolean) RETURNS bigint
LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
DECLARE
    s text := btrim(input, E' \t\r\n');
    p integer;
    r text;
    sign text := '';
    ydigits text;
    y bigint;
    mon integer := 1;
    dom integer := 1;
    prec integer;        -- 1 year, 2 month, 3 day, 4 time
    hh integer := 0;
    mi integer := 0;
    ss integer := 0;
    ms integer := 0;
    mlen integer;
    cy bigint;           -- the civil date whose midnight the result counts from
    cm integer;
    cd integer;
    offs bigint;         -- milliseconds after that midnight
    yy bigint;
    era bigint;
    yoe bigint;
    doe bigint;
    result bigint;
BEGIN
    -- Each form is recognised by a match without captures, which is several times cheaper than
    -- regexp_match, and its fields are then read by position.
    IF s ~ '^-?[0-9]+(-[0-9]{2}(-[0-9]{2}(T[0-9]{2}:[0-9]{2}(:[0-9]{2}([.][0-9]+)?)?)?)?)?$' THEN
        -- [-]Y[-MM[-DD[Thh:mm[:ss[.fraction]]]]]
        IF left(s, 1) = '-' THEN
            sign := '-'; s := substr(s, 2);
        END IF;
        p := strpos(s, '-');
        prec := 1;
        IF p = 0 THEN
            ydigits := s;
        ELSE
            ydigits := substr(s, 1, p - 1);
            r := substr(s, p + 1);   -- MM-DDThh:mm:ss.fraction, cut short where the input ends
            -- Y-00-00 names the year alone; any other zero month or day fails validation below.
            IF r <> '00-00' THEN
                mon := substr(r, 1, 2)::integer; prec := 2;
                IF length(r) > 2 THEN
                    dom := substr(r, 4, 2)::integer; prec := 3;
                END IF;
                IF length(r) > 5 THEN
                    hh := substr(r, 7, 2)::integer; mi := substr(r, 10, 2)::integer; prec := 4;
                END IF;
                IF length(r) > 11 THEN
                    ss := substr(r, 13, 2)::integer;
                END IF;
                IF length(r) > 14 THEN
                    ms := rpad(substr(r, 16, 3), 3, '0')::integer;
                END IF;
            END IF;
        END IF;
    ELSIF s ~ '^00/00/[0-9]+$' THEN
        ydigits := substr(s, 7); prec := 1;
    ELSIF s ~ '^[0-9]{1,2}/[0-9]{1,2}/[0-9]{4}$' THEN
        dom := split_part(s, '/', 1)::integer;
        mon := split_part(s, '/', 2)::integer;
        ydigits := split_part(s, '/', 3);
        prec := 3;
    ELSE
        RETURN NULL;
    END IF;

    -- Year: strip leading zeros, guard the length before any cast, reject year zero.
    ydigits := ltrim(ydigits, '0');
    IF ydigits = '' OR length(ydigits) > 7 THEN
        RETURN NULL;
    END IF;
    y := ydigits::bigint;
    IF sign = '-' THEN
        y := 1 - y;      -- Y BCE is astronomical 1 - Y
    END IF;

    -- Calendar validation.
    IF mon < 1 OR mon > 12 OR hh > 23 OR mi > 59 OR ss > 59 OR dom < 1 THEN
        RETURN NULL;
    END IF;
    mlen := CASE mon
        WHEN 2 THEN CASE WHEN (y % 4 = 0 AND y % 100 <> 0) OR y % 400 = 0 THEN 29 ELSE 28 END
        WHEN 4 THEN 30 WHEN 6 THEN 30 WHEN 9 THEN 30 WHEN 11 THEN 30
        ELSE 31 END;
    IF dom > mlen THEN
        RETURN NULL;
    END IF;

    -- The instant as a civil date's midnight plus an offset.
    IF prec = 4 THEN
        cy := y; cm := mon; cd := dom;
        offs := hh * 3600000::bigint + mi * 60000 + ss * 1000 + ms;
    ELSIF NOT is_end THEN
        cy := y; cm := mon; cd := dom; offs := 0;
    ELSIF prec = 3 THEN
        cy := y; cm := mon; cd := dom; offs := 86400000 - 1;
    ELSIF prec = 2 THEN
        cy := CASE WHEN mon = 12 THEN y + 1 ELSE y END;
        cm := CASE WHEN mon = 12 THEN 1 ELSE mon + 1 END;
        cd := 1; offs := -1;
    ELSE
        cy := y + 1; cm := 1; cd := 1; offs := -1;
    END IF;

    -- Epoch milliseconds of 00:00:00 UTC on cy-cm-cd, proleptic Gregorian, astronomical years.
    -- Days are counted in 400-year eras from 0000-03-01; era is the floor of yy / 400.
    yy := cy - CASE WHEN cm <= 2 THEN 1 ELSE 0 END;
    era := (CASE WHEN yy >= 0 THEN yy ELSE yy - 399 END) / 400;
    yoe := yy - era * 400;
    doe := yoe * 365 + yoe / 4 - yoe / 100
        + (153 * (cm + CASE WHEN cm > 2 THEN -3 ELSE 9 END) + 2) / 5 + cd - 1;
    result := (era * 146097 + doe - 719468) * 86400000 + offs;

    IF abs(result) > 9007199254740991 THEN
        RETURN NULL;
    END IF;
    RETURN result;
END
$fn$
SQL
        );

        // SQL wrappers, so the planner can inline them.
        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_udate(input text) RETURNS bigint
LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
SELECT tlcmap.v2_udate_parts(input, false)
$fn$
SQL
        );

        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_udate_end(input text) RETURNS bigint
LANGUAGE sql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
SELECT tlcmap.v2_udate_parts(input, true)
$fn$
SQL
        );
    }

    public function down()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_udate_end(text)');
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_udate(text)');
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_udate_parts(text, boolean)');
    }
}
