<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateV2ExtendedValueFunction extends Migration
{
    public function up()
    {
        // The value of the first Data element, in document order, whose name attribute equals field.
        // NULL for NULL, blank or malformed input. No exception block: it would open a subtransaction
        // per row. xpath() returns string() results XML-escaped, so they are unescaped here, with
        // the ampersand last. The field name is only ever compared, never put into an XPath.
        DB::unprepared(<<<'SQL'
CREATE FUNCTION tlcmap.v2_extended_value(xml_text text, field text) RETURNS text
LANGUAGE sql IMMUTABLE PARALLEL SAFE AS $fn$
SELECT CASE
    WHEN xml_text IS NULL OR field IS NULL OR btrim(xml_text, E' \t\r\n') = '' THEN NULL
    WHEN NOT xml_is_well_formed_document(xml_text) THEN NULL
    ELSE (
        SELECT replace(replace(replace(
                   (xpath('string(/Data/value)', d.node))[1]::text,
                   '&lt;', '<'), '&gt;', '>'), '&amp;', '&')
        FROM unnest(xpath('//Data', xml_text::xml)) WITH ORDINALITY AS d(node, ord)
        WHERE replace(replace(replace(
                  (xpath('string(/Data/@name)', d.node))[1]::text,
                  '&lt;', '<'), '&gt;', '>'), '&amp;', '&') = field
        ORDER BY d.ord
        LIMIT 1
    )
END
$fn$
SQL
        );
    }

    public function down()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS tlcmap.v2_extended_value(text, text)');
    }
}
