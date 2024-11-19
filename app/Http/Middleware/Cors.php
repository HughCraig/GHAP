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
        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', '*')
                     ->header('Access-Control-Allow-Methods', 'GET, POST')
                     ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
                     ->header('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }
}
