<?php

namespace TLCMap\Api\V2\Support;

use Illuminate\Http\JsonResponse;

class Json
{
    const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

    /**
     * Every v2 response is built here: compact JSON, slashes and Unicode left as they are. Invalid
     * UTF-8, for which requests are refused, becomes U+FFFD here rather than a failure to encode.
     */
    public static function response(array $body, int $status = 200, array $headers = []): JsonResponse
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new JsonResponse($body, $status, $headers, self::FLAGS);
    }
}
