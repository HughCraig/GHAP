<?php

namespace TLCMap\Http\Middleware;

use Closure;

class ApiV2Cors
{
    public function handle($request, Closure $next)
    {
        return self::apply($next($request));
    }

    /**
     * No credentials header: v2 is public and cookieless.
     */
    public static function apply($response)
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type');

        return $response;
    }
}
