<?php

namespace Tests\V2\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use TLCMap\Api\V2\Support\Json;
use Tests\V2\TestCase;

class RateLimitTest extends TestCase
{
    const KEY = 'tlcmap_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    const KEY2 = 'tlcmap_BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';
    const OK = '/api/v2/__test/ok';

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
        });

        config([
            'api_v2.rate_limit' => '2,1',
            'api_v2.client_rate_limit' => '5,1',
            'api_v2.clients' => 'mcp-test:' . hash('sha256', self::KEY)
                . ';mcp-own:' . hash('sha256', self::KEY2) . ':3,1',
        ]);
    }

    private function bearer($key)
    {
        return ['Authorization' => "Bearer $key"];
    }

    public function test_anonymous_limit_and_headers()
    {
        $this->get(self::OK)->assertStatus(200)
            ->assertHeader('X-RateLimit-Limit', 2)->assertHeader('X-RateLimit-Remaining', 1);
        $this->get(self::OK)->assertStatus(200)->assertHeader('X-RateLimit-Remaining', 0);
        $r = $this->get(self::OK);
        $r->assertStatus(429);
        $this->assertSame('RATE_LIMITED', $r->json('error.code'));
        $this->assertSame(2, $r->json('error.details.limit'));
    }

    public function test_client_default_limit()
    {
        $this->get(self::OK, $this->bearer(self::KEY))->assertStatus(200)->assertHeader('X-RateLimit-Limit', 5);
        for ($i = 0; $i < 4; $i++) {
            $this->get(self::OK, $this->bearer(self::KEY))->assertStatus(200);
        }
        $r = $this->get(self::OK, $this->bearer(self::KEY));
        $r->assertStatus(429);
        $this->assertSame(5, $r->json('error.details.limit'));
    }

    public function test_client_own_limit()
    {
        $this->get(self::OK, $this->bearer(self::KEY2))->assertStatus(200)->assertHeader('X-RateLimit-Limit', 3);
    }

    public function test_client_and_address_counted_apart()
    {
        for ($i = 0; $i < 3; $i++) {
            $this->get(self::OK, $this->bearer(self::KEY))->assertStatus(200);
        }
        $this->get(self::OK)->assertStatus(200);
        $this->get(self::OK)->assertStatus(200);
        $this->get(self::OK)->assertStatus(429);
        $this->get(self::OK, $this->bearer(self::KEY))->assertStatus(200);
    }

    public function test_scheme_is_case_insensitive_and_spaces_tolerated()
    {
        $this->get(self::OK, ['Authorization' => 'bearer   ' . self::KEY . ' '])
            ->assertStatus(200)->assertHeader('X-RateLimit-Limit', 5);
    }

    /** @dataProvider wrongCredentials */
    public function test_wrong_credential_is_401($header)
    {
        $r = $this->get(self::OK, ['Authorization' => $header]);
        $r->assertStatus(401);
        $this->assertSame('INVALID_CREDENTIAL', $r->json('error.code'));
        $r->assertHeader('WWW-Authenticate', 'Bearer error="invalid_token"');
        $r->assertHeader('Access-Control-Allow-Origin', '*');
        $r->assertHeader('X-RateLimit-Limit', 2);
    }

    public function wrongCredentials()
    {
        return [
            'unknown key' => ['Bearer tlcmap_' . str_repeat('C', 43)],
            'other scheme' => ['Basic dXNlcjpwYXNz'],
            'empty token' => ['Bearer '],
            'no token' => ['Bearer'],
            'no scheme' => [self::KEY],
        ];
    }

    public function test_wrong_credential_counts_against_the_address()
    {
        $wrong = ['Authorization' => 'Bearer tlcmap_' . str_repeat('C', 43)];
        $this->get(self::OK, $wrong)->assertStatus(401)->assertHeader('X-RateLimit-Remaining', 1);
        $this->get(self::OK, $wrong)->assertStatus(401)->assertHeader('X-RateLimit-Remaining', 0);
        $this->get(self::OK)->assertStatus(429);
    }

    public function test_wrong_credential_past_the_limit_is_429()
    {
        $this->get(self::OK)->assertStatus(200);
        $this->get(self::OK)->assertStatus(200);
        $this->get(self::OK, ['Authorization' => 'Bearer tlcmap_' . str_repeat('C', 43)])->assertStatus(429);
    }

    public function test_malformed_clients_fail_every_v2_request()
    {
        Log::spy();
        config(['api_v2.clients' => hash('sha256', self::KEY) . ':mcp-x']);
        foreach (['/api/v2/__test/ok', '/api/v2/vocabulary?field=feature_term'] as $path) {
            $r = $this->get($path);
            $r->assertStatus(500);
            $this->assertSame('INTERNAL_ERROR', $r->json('error.code'));
            $this->assertStringNotContainsString(hash('sha256', self::KEY), $r->getContent());
        }
        $fragment = substr(hash('sha256', self::KEY), 0, 12);
        Log::shouldHaveReceived('error')->withArgs(function ($message) {
            return strpos($message, 'API_V2_CLIENTS entry 1') === 0
                && strpos($message, hash('sha256', self::KEY)) === false;
        });
        Log::shouldHaveReceived('error')->withArgs(function ($message, $context = []) use ($fragment) {
            $logged = $message;
            if (isset($context['exception']) && $context['exception'] instanceof \Throwable) {
                $logged .= $context['exception']->getMessage() . $context['exception']->getTraceAsString();
            }
            $this->assertStringNotContainsString($fragment, $logged);
            return true;
        });
    }

    public function test_malformed_anonymous_limit_fails()
    {
        config(['api_v2.rate_limit' => '60']);
        $r = $this->get(self::OK);
        $r->assertStatus(500);
        $this->assertSame('INTERNAL_ERROR', $r->json('error.code'));
    }

    public function test_key_and_hash_never_in_a_response()
    {
        $bodies = [$this->get(self::OK, $this->bearer(self::KEY))->getContent(),
            $this->get(self::OK, $this->bearer(self::KEY . 'x'))->getContent()];
        for ($i = 0; $i < 5; $i++) {
            $last = $this->get(self::OK, $this->bearer(self::KEY));
        }
        $last->assertStatus(429);
        $bodies[] = $last->getContent();
        foreach ($bodies as $body) {
            $this->assertStringNotContainsString(self::KEY, $body);
            $this->assertStringNotContainsString(hash('sha256', self::KEY), $body);
        }
    }

    public function test_preflight_unchanged()
    {
        $r = $this->call('OPTIONS', self::OK);
        $r->assertStatus(204);
        $r->assertHeader('Access-Control-Allow-Headers', 'Content-Type');
    }
}
