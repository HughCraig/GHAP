<?php

namespace TLCMap\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Routing\Middleware\ThrottleRequests;
use TLCMap\Api\V2\Clients\ClientRegistry;
use TLCMap\Api\V2\Clients\ClientConfigurationException;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Errors\ErrorRenderer;

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
        try {
            $registry = ClientRegistry::fromConfig();
            if ($maxAttempts === null || $decayMinutes === null) {
                list($maxAttempts, $decayMinutes) = ClientRegistry::parseLimit((string) config('api_v2.rate_limit'), 'API_V2_RATE_LIMIT');
            }
        } catch (ClientConfigurationException $e) {
            // Log the message alone: the exception's trace frames would print the start of the raw configuration.
            Log::error($e->getMessage());
            throw new \RuntimeException('Invalid API v2 rate limit configuration.');
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
                // Returned, not thrown, so the rate-limit headers are added to the 401 (spec section 5.3).
                $next = function ($request) {
                    return ErrorRenderer::render($request, ApiException::invalidCredential());
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
