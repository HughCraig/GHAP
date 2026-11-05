<?php

namespace TLCMap\Api\V2\Support;

use Illuminate\Http\JsonResponse;

class Json
{
    const FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Every v2 response is built here: compact JSON, slashes and Unicode left as they are.
     */
    public static function response(array $body, int $status = 200, array $headers = []): JsonResponse
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';

        return new JsonResponse($body, $status, $headers, self::FLAGS);
    }
}
