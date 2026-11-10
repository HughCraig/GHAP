<?php

namespace Tests\V2\Unit;

use Tests\V2\TestCase;
use TLCMap\Api\V2\Clients\ClientConfigurationException;
use TLCMap\Api\V2\Clients\ClientRegistry;

class ClientRegistryTest extends TestCase
{
    private const KEY = 'tlcmap_AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA';
    private const KEY2 = 'tlcmap_BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB';

    private static function hash(): string
    {
        return hash('sha256', self::KEY);
    }

    private static function hash2(): string
    {
        return hash('sha256', self::KEY2);
    }

    public function test_empty_or_null_means_no_clients()
    {
        $this->assertNull((new ClientRegistry(null, '600,1'))->find(self::KEY));
        $this->assertNull((new ClientRegistry('', '600,1'))->find(self::KEY));
        $this->assertNull((new ClientRegistry(' ; ', '600,1'))->find(self::KEY));
    }

    public function test_finds_a_client_with_its_own_limit()
    {
        $c = (new ClientRegistry('mcp-staging:' . self::hash() . ':120,2', '600,1'))->find(self::KEY);
        $this->assertSame(['mcp-staging', 120, 2], [$c->name(), $c->maxAttempts(), $c->decayMinutes()]);
    }

    public function test_entry_without_limit_takes_the_default()
    {
        $c = (new ClientRegistry('a:' . self::hash(), '600,1'))->find(self::KEY);
        $this->assertSame([600, 1], [$c->maxAttempts(), $c->decayMinutes()]);
    }

    public function test_whitespace_and_trailing_semicolon_ignored()
    {
        $r = new ClientRegistry(' a:' . self::hash() . ' ; b:' . self::hash2() . ' ;', '600,1');
        $this->assertSame('a', $r->find(self::KEY)->name());
        $this->assertSame('b', $r->find(self::KEY2)->name());
    }

    public function test_unknown_key_finds_nothing()
    {
        $this->assertNull((new ClientRegistry('a:' . self::hash(), '600,1'))->find(self::KEY2));
    }

    public function test_empty_key_finds_nothing()
    {
        $this->assertNull((new ClientRegistry('a:' . self::hash(), '600,1'))->find(''));
    }

    /**
     * @dataProvider malformedProvider
     */
    public function test_malformed_value_is_refused(string $value, string $entry, string $reason)
    {
        try {
            new ClientRegistry($value, '600,1');
            $this->fail('Expected a ClientConfigurationException.');
        } catch (ClientConfigurationException $e) {
            $this->assertStringStartsWith('API_V2_CLIENTS entry ' . $entry, $e->getMessage());
            $this->assertStringContainsString($reason, $e->getMessage());
        }
    }

    public function malformedProvider(): array
    {
        $h = hash('sha256', self::KEY);
        $h2 = hash('sha256', self::KEY2);

        return [
            'uppercase name' => ['Bad_Name:' . $h, '1', 'name'],
            'name too long' => [str_repeat('a', 41) . ':' . $h, '1', 'name'],
            'uppercase hash' => ['a:' . strtoupper($h), '1', 'hash'],
            'short hash' => ['a:abc', '1', 'hash'],
            'zero limit' => ['a:' . $h . ':0,1', '1', 'limit'],
            'limit without minutes' => ['a:' . $h . ':60', '1', 'limit'],
            'too many fields' => ['a:' . $h . ':60,1:x', '1', 'name:hash or name:hash:limit'],
            'one field' => ['a', '1', 'name:hash or name:hash:limit'],
            'duplicate name' => ['a:' . $h . ';a:' . $h2, '2', 'duplicate name'],
            'duplicate hash' => ['a:' . $h . ';b:' . $h, '2', 'duplicate hash'],
        ];
    }

    public function test_refusal_names_a_valid_name()
    {
        try {
            new ClientRegistry('mcp-x:abc', '600,1');
            $this->fail('Expected a ClientConfigurationException.');
        } catch (ClientConfigurationException $e) {
            $this->assertStringContainsString('(mcp-x)', $e->getMessage());
        }
    }

    public function test_refusal_never_contains_a_hash()
    {
        // Fields in the wrong order: the "name" is a hash.
        try {
            new ClientRegistry(self::hash() . ':mcp-x', '600,1');
            $this->fail('Expected a ClientConfigurationException.');
        } catch (ClientConfigurationException $e) {
            $this->assertStringContainsString('API_V2_CLIENTS entry 1', $e->getMessage());
            $this->assertStringNotContainsString(self::hash(), $e->getMessage());
        }
    }

    public function test_malformed_default_limit_is_refused()
    {
        try {
            new ClientRegistry(null, '600');
            $this->fail('Expected a ClientConfigurationException.');
        } catch (ClientConfigurationException $e) {
            $this->assertStringStartsWith('API_V2_CLIENT_RATE_LIMIT', $e->getMessage());
        }
    }

    public function test_from_config_reads_both_keys()
    {
        config([
            'api_v2.clients' => 'a:' . self::hash(),
            'api_v2.client_rate_limit' => '7,3',
        ]);

        $c = ClientRegistry::fromConfig()->find(self::KEY);
        $this->assertSame(['a', 7, 3], [$c->name(), $c->maxAttempts(), $c->decayMinutes()]);
    }
}
