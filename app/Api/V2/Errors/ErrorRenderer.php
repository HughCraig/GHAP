<?php

namespace TLCMap\Api\V2\Errors;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TLCMap\Api\V2\Support\Json;
use TLCMap\Http\Middleware\ApiV2Cors;

class ErrorRenderer
{
    /**
     * Any exception on a v2 path becomes the one error body of spec section 11. Laravel renders 404 and
     * 405 outside the route middleware, so the CORS headers are added here as well.
     */
    public static function render(Request $request, \Exception $e): JsonResponse
    {
        $headers = [];

        if ($e instanceof ApiException) {
            $status = $e->status();
            $code = $e->errorCode();
            $message = $e->getMessage();
            $details = $e->details();
        } elseif ($e instanceof ThrottleRequestsException) {
            $status = 429;
            $code = 'RATE_LIMITED';
            $message = 'Too many requests. Wait before trying again.';
            $thrown = $e->getHeaders();
            $details = [
                'limit' => isset($thrown['X-RateLimit-Limit']) ? (int) $thrown['X-RateLimit-Limit'] : null,
                'retry_after' => isset($thrown['Retry-After']) ? (int) $thrown['Retry-After'] : null,
            ];
            $headers = $thrown;
        } elseif ($e instanceof MethodNotAllowedHttpException) {
            $status = 405;
            $code = 'METHOD_NOT_ALLOWED';
            $message = 'Only GET and OPTIONS are accepted.';
            $details = ['method' => $request->method()];
            $headers = $e->getHeaders();
        } elseif ($e instanceof NotFoundHttpException) {
            $status = 404;
            $code = 'NOT_FOUND';
            $message = 'No such v2 endpoint.';
            $details = ['path' => $request->path()];
        } else {
            $status = 500;
            $code = 'INTERNAL_ERROR';
            $message = 'An unexpected error occurred.';
            $details = [];
        }

        $response = Json::response([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => (object) $details,
            ],
        ], $status, $headers);

        return ApiV2Cors::apply($response);
    }
}
