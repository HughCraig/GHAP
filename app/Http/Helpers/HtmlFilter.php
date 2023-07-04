<?php

namespace TLCMap\Http\Helpers;

class HtmlFilter
{
    /**
     * Filter and output simple HTML.
     *
     * @param string $raw
     *   The raw HTML.
     * @return string
     *   The filtered HTML.
     */
    public static function simple($raw)
    {
        if (!empty($raw)) {
            return strip_tags($raw, '<h2><h3><h4><h5><p><strong><em><a><ul><li><br><table><tr><td><th><tbody><thead><tfoot>');
        }
        return '';
    }
}
