<?php

namespace TLCMap\Http\Helpers;

class URL
{
    /**
     * Replace all URLs within the text to HTML links.
     *
     * @param $text
     *   The input text.
     * @return string
     *   The output text.
     */
    public static function replaceUrlToHtml($text)
    {
        return preg_replace('/\b(https?:\/\/\S+)/i', '<a href="$1">$1</a>', $text);
    }
}
