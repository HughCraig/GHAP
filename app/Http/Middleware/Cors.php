<?php

namespace TLCMap\Http\Middleware;

use Closure;

class Cors
{
    /**
     * Handle an incoming request to allow any origin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        if ($response instanceof \Illuminate\Http\Response) {
            return $response
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET');
        }
        return $response;
    }
}
