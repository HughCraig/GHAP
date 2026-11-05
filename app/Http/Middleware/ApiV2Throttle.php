<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;

class ApiV2Throttle extends ThrottleRequests
{
    public function handle($request, Closure $next, $maxAttempts = null, $decayMinutes = null)
    {
        if ($maxAttempts === null || $decayMinutes === null) {
            list($maxAttempts, $decayMinutes) = array_map('intval', explode(',', (string) config('api_v2.rate_limit')));
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes);
    }

    /**
     * Prefixed so v2 requests never share a counter with v1's, which uses the same address signature.
     */
    protected function resolveRequestSignature($request)
    {
        return 'api-v2|' . parent::resolveRequestSignature($request);
    }
}
