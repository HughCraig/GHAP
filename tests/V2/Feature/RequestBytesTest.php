<?php

namespace Tests\V2\Feature;

use Tests\V2\Support\Fixtures;
use Tests\V2\TestCase;
use TLCMap\Api\V2\Support\Json;

/** Request bytes that are not text, and ids too long to be layers: refused, never a 500. */
class RequestBytesTest extends TestCase
{
    private function assertEncodingRejected(string $uri, string $parameter): void
    {
        $r = $this->get($uri);
        $r->assertStatus(400);
        $this->assertStringStartsWith('application/json', $r->headers->get('Content-Type'));
        $body = json_decode($r->getContent(), true);
        $this->assertIsArray($body, 'The error body is JSON.');
        $this->assertSame('INVALID_PARAMETER', $body['error']['code']);
        $this->assertSame($parameter, $body['error']['details']['parameter']);
        $this->assertSame('encoding', $body['error']['details']['rule']);
        $this->assertSame('UTF-8 text without NUL characters', $body['error']['details']['accepts']);
    }

    public function invalidValues(): array
    {
        return [
            'name, invalid byte' => ['/api/v2/places?name=%FF', 'name'],
            'name, truncated sequence' => ['/api/v2/places?name=Ngun%E2%80', 'name'],
            'name, NUL' => ['/api/v2/places?name=a%00b', 'name'],
            'seed, invalid byte' => ['/api/v2/places?sort=random&seed=%C3%28', 'seed'],
            'extended_data, NUL' => ['/api/v2/places?extended_data=k%20%3D%207%00x', 'extended_data'],
            'q, invalid byte' => ['/api/v2/layers?q=%FF', 'q'],
            'q, NUL' => ['/api/v2/layers?q=a%00', 'q'],
            'features, cursor' => ['/api/v2/layers/1/features?cursor=%FF', 'cursor'],
        ];
    }

    /** @dataProvider invalidValues */
    public function test_value_that_is_not_text_is_rejected(string $uri, string $parameter)
    {
        $this->assertEncodingRejected($uri, $parameter);
    }

    public function test_parameter_name_that_is_not_text_is_rejected_and_named_readably()
    {
        $this->assertEncodingRejected('/api/v2/places?%FF=1', "\u{FFFD}");
        $this->assertEncodingRejected('/api/v2/layers?a%FFb=1', "a\u{FFFD}b");
        $this->assertEncodingRejected('/api/v2/places?na%00me=1', "na\u{FFFD}me");
    }

    public function test_nul_never_shortens_a_name_match()
    {
        $layer = Fixtures::layer();
        Fixtures::record(['dataset_id' => $layer, 'title' => 'a']);

        $this->assertSame(1, $this->get("/api/v2/places?summary_only=true&layers=$layer&name=a")->json('summary.total'));
        $this->assertEncodingRejected("/api/v2/places?summary_only=true&layers=$layer&name=a%00b", 'name');
    }

    public function test_json_substitutes_invalid_utf8_rather_than_failing()
    {
        $response = Json::response(['text' => "a\xFFb"]);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame("{\"text\":\"a\u{FFFD}b\"}", $response->getContent());
    }

    public function overlongIds(): array
    {
        return [
            'detail' => ['/api/v2/layers/99999999999999999999'],
            'features' => ['/api/v2/layers/99999999999999999999/features'],
            'nineteen digits' => ['/api/v2/layers/1000000000000000000'],
            'leading zeros' => ['/api/v2/layers/0000000000000000001'],
        ];
    }

    /** @dataProvider overlongIds */
    public function test_id_longer_than_eighteen_digits_is_not_found(string $uri)
    {
        foreach (['GET', 'OPTIONS'] as $method) {
            $r = $this->call($method, $uri);
            $r->assertStatus(404);
            $this->assertSame('NOT_FOUND', $r->json('error.code'), $method);
        }
    }

    public function test_eighteen_digit_id_reaches_the_layer_gate()
    {
        $r = $this->get('/api/v2/layers/999999999999999999');
        $r->assertStatus(404);
        $this->assertSame('LAYER_NOT_FOUND', $r->json('error.code'));
        $this->assertSame(999999999999999999, $r->json('error.details.layer_id'));

        $this->call('OPTIONS', '/api/v2/layers/999999999999999999/features')->assertStatus(204);
    }
}
