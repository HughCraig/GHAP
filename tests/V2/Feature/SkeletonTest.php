<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\Route;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Support\Json;
use Tests\V2\TestCase;

class SkeletonTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('api_v2')->group(function () {
            Route::options('api/v2/__test/ok', function () {
                return response('', 204);
            });
            Route::get('api/v2/__test/ok', function () {
                return Json::response(['ok' => true]);
            });
            Route::get('api/v2/__test/boom', function () {
                throw new \RuntimeException('secret detail');
            });
            Route::get('api/v2/__test/private', function () {
                throw ApiException::layerPrivate(7);
            });
        });
    }

    public function test_unknown_v2_route_is_json_404()
    {
        $r = $this->get('/api/v2/nope');
        $r->assertStatus(404);
        $this->assertSame('NOT_FOUND', $r->json('error.code'));
        $this->assertSame('api/v2/nope', $r->json('error.details.path'));
    }

    public function test_post_is_json_405()
    {
        $r = $this->post('/api/v2/__test/ok');
        $r->assertStatus(405);
        $this->assertSame('METHOD_NOT_ALLOWED', $r->json('error.code'));
        $this->assertSame('POST', $r->json('error.details.method'));
    }

    public function test_options_preflight()
    {
        $r = $this->call('OPTIONS', '/api/v2/__test/ok');
        $r->assertStatus(204);
        $r->assertHeader('Access-Control-Allow-Origin', '*');
        $r->assertHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $r->assertHeaderMissing('Access-Control-Allow-Credentials');
    }

    public function test_cors_on_errors()
    {
        $this->get('/api/v2/nope')->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_unexpected_error_is_opaque_500()
    {
        $r = $this->get('/api/v2/__test/boom');
        $r->assertStatus(500);
        $this->assertSame('INTERNAL_ERROR', $r->json('error.code'));
        $this->assertStringNotContainsString('secret detail', $r->getContent());
    }

    public function test_api_exception_rendered()
    {
        $r = $this->get('/api/v2/__test/private');
        $r->assertStatus(403);
        $this->assertSame('LAYER_PRIVATE', $r->json('error.code'));
        $this->assertNotEmpty($r->json('error.message'));
        $this->assertSame(['layer_id' => 7], $r->json('error.details'));
    }

    public function test_rate_limited()
    {
        config(['api_v2.rate_limit' => '2,1']);
        $this->get('/api/v2/__test/ok')->assertStatus(200);
        $this->get('/api/v2/__test/ok')->assertStatus(200);
        $r = $this->get('/api/v2/__test/ok');
        $r->assertStatus(429);
        $this->assertSame('RATE_LIMITED', $r->json('error.code'));
        $r->assertHeader('Retry-After');
        $this->assertSame(2, $r->json('error.details.limit'));
        $r->assertHeader('Access-Control-Allow-Origin', '*');
    }

    public function test_v1_requests_do_not_count_against_v2()
    {
        config(['api_v2.rate_limit' => '2,1']);
        for ($i = 0; $i < 3; $i++) {
            $this->get('/layers/json');
        }
        $this->get('/api/v2/__test/ok')->assertStatus(200);
    }

    public function test_v2_requests_do_not_count_against_v1()
    {
        for ($i = 0; $i < 45; $i++) {
            $this->get('/api/v2/__test/ok');
        }
        $this->assertNotSame(429, $this->get('/layers/json')->getStatusCode());
    }

    public function test_non_v2_paths_untouched()
    {
        $r = $this->get('/nope');
        $this->assertStringNotContainsString('json', (string) $r->headers->get('Content-Type'));
    }

    public function test_response_flags()
    {
        $r = $this->get('/api/v2/__test/ok');
        $this->assertSame('{"ok":true}', $r->getContent());
        $this->assertSame('application/json; charset=utf-8', $r->headers->get('Content-Type'));
    }
}
