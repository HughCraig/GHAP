<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Illuminate\Routing\Middleware\ThrottleRequests;
use TLCMap\Api\V2\Clients\ClientRegistry;
use TLCMap\Api\V2\Errors\ApiException;

class ApiV2Throttle extends ThrottleRequests
{
    /** @var string */
    private $signature = '';

    /**
     * Counts the request against the presented client, or against the address when there is no credential or
     * it does not match. Configuration is parsed on every request, so a malformed value fails every v2 request.
     */
    public function handle($request, Closure $next, $maxAttempts = null, $decayMinutes = null)
    {
        $registry = ClientRegistry::fromConfig();
        if ($maxAttempts === null || $decayMinutes === null) {
            list($maxAttempts, $decayMinutes) = ClientRegistry::parseLimit((string) config('api_v2.rate_limit'), 'API_V2_RATE_LIMIT');
        }

        $this->signature = 'api-v2|' . parent::resolveRequestSignature($request);

        $header = $request->headers->get('Authorization');
        if ($header !== null) {
            $client = null;
            if (preg_match('/^bearer +(.*)$/i', $header, $m)) {
                $client = $registry->find(trim($m[1]));
            }
            if ($client !== null) {
                $this->signature = 'api-v2|client|' . $client->name();
                $maxAttempts = $client->maxAttempts();
                $decayMinutes = $client->decayMinutes();
            } else {
                $next = function () {
                    throw ApiException::invalidCredential();
                };
            }
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes);
    }

    /**
     * The signature chosen in handle(); v2 addresses are prefixed so they never share a counter with v1.
     */
    protected function resolveRequestSignature($request)
    {
        return $this->signature;
    }
}
