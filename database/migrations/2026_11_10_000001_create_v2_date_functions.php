<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateV2DateFunctions extends Migration
{
    public function up()
    {
        // Epoch milliseconds of 00:00:00 UTC on a proleptic Gregorian date, astronomical year numbering.
        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_civil_ms(y bigint, m integer, d integer) RETURNS bigint
LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
DECLARE
    yy bigint := y - CASE WHEN m <= 2 THEN 1 ELSE 0 END;
    era bigint := floor(yy::numeric / 400)::bigint;
    yoe bigint := yy - era * 400;
    doy bigint := (153 * (m + CASE WHEN m > 2 THEN -3 ELSE 9 END) + 2) / 5 + d - 1;
    doe bigint := yoe * 365 + yoe / 4 - yoe / 100 + doy;
BEGIN
    RETURN (era * 146097 + doe - 719468) * 86400000;
END
$fn$
SQL
        );

        // The single parse. is_end = false gives the first instant of the period named, true the last.
        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_udate_parts(input text, is_end boolean) RETURNS bigint
LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
DECLARE
    s text := btrim(input, E' \t\r\n');
    m text[];
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
    result bigint;
    ny bigint;
    nm integer;
BEGIN
    m := regexp_match(s, '^(-?)([0-9]+)-00-00$');
    IF m IS NOT NULL THEN
        sign := m[1]; ydigits := m[2]; prec := 1;
    ELSE
        m := regexp_match(s, '^00/00/([0-9]+)$');
        IF m IS NOT NULL THEN
            ydigits := m[1]; prec := 1;
        ELSE
            m := regexp_match(s, '^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$');
            IF m IS NOT NULL THEN
                dom := m[1]::integer; mon := m[2]::integer; ydigits := m[3]; prec := 3;
            ELSE
                m := regexp_match(s, '^(-?)([0-9]+)(?:-([0-9]{2})(?:-([0-9]{2})(?:T([0-9]{2}):([0-9]{2})(?::([0-9]{2})(?:\.([0-9]+))?)?)?)?)?$');
                IF m IS NULL THEN
                    RETURN NULL;
                END IF;
                sign := m[1]; ydigits := m[2];
                prec := 1;
                IF m[3] IS NOT NULL THEN mon := m[3]::integer; prec := 2; END IF;
                IF m[4] IS NOT NULL THEN dom := m[4]::integer; prec := 3; END IF;
                IF m[5] IS NOT NULL THEN
                    hh := m[5]::integer; mi := m[6]::integer; prec := 4;
                    IF m[7] IS NOT NULL THEN ss := m[7]::integer; END IF;
                    IF m[8] IS NOT NULL THEN ms := rpad(substr(m[8], 1, 3), 3, '0')::integer; END IF;
                END IF;
            END IF;
        END IF;
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

    IF prec = 4 THEN
        result := tlcmap.v2_civil_ms(y, mon, dom) + hh * 3600000::bigint + mi * 60000 + ss * 1000 + ms;
    ELSIF NOT is_end THEN
        result := tlcmap.v2_civil_ms(y, mon, dom);
    ELSIF prec = 3 THEN
        result := tlcmap.v2_civil_ms(y, mon, dom) + 86400000 - 1;
    ELSIF prec = 2 THEN
        ny := CASE WHEN mon = 12 THEN y + 1 ELSE y END;
        nm := CASE WHEN mon = 12 THEN 1 ELSE mon + 1 END;
        result := tlcmap.v2_civil_ms(ny, nm, 1) - 1;
    ELSE
        result := tlcmap.v2_civil_ms(y + 1, 1, 1) - 1;
    END IF;

    IF abs(result) > 9007199254740991 THEN
        RETURN NULL;
    END IF;
    RETURN result;
END
$fn$
SQL
        );

        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_udate(input text) RETURNS bigint
LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
BEGIN
    RETURN tlcmap.v2_udate_parts(input, false);
END
$fn$
SQL
        );

        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_udate_end(input text) RETURNS bigint
LANGUAGE plpgsql IMMUTABLE STRICT PARALLEL SAFE AS $fn$
BEGIN
    RETURN tlcmap.v2_udate_parts(input, true);
END
$fn$
SQL
        );
    }

    public function down()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_udate_end(text)');
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_udate(text)');
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_udate_parts(text, boolean)');
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_civil_ms(bigint, integer, integer)');
    }
}
