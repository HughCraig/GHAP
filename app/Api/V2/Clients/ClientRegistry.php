<?php

namespace TLCMap\Api\V2\Clients;

/**
 * The v2 API clients, parsed from API_V2_CLIENTS. Each entry is name:hash or name:hash:limit,
 * entries separated by ";". The hash is the SHA-256 of the client's key.
 */
final class ClientRegistry
{
    private const NAME = '/^[a-z0-9-]{1,40}$/D';
    private const HASH = '/^[0-9a-f]{64}$/D';

    /** @var array hash => Client */
    private $byHash = [];

    public function __construct(?string $clients, string $defaultLimit)
    {
        // The default is parsed first so that a bad one is reported even when there are no clients.
        list($defaultMax, $defaultMinutes) = self::parseLimit($defaultLimit, 'API_V2_CLIENT_RATE_LIMIT');

        $entries = array_values(array_filter(array_map('trim', explode(';', (string) $clients)), function ($entry) {
            return $entry !== '';
        }));

        foreach ($entries as $index => $entry) {
            $client = $this->parseEntry($entry, $index + 1, $defaultMax, $defaultMinutes);

            foreach ($this->byHash as $existing) {
                if ($existing->name() === $client[1]->name()) {
                    throw self::refusal($index + 1, $client[1]->name(), 'duplicate name');
                }
            }
            if (isset($this->byHash[$client[0]])) {
                throw self::refusal($index + 1, $client[1]->name(), 'duplicate hash');
            }
            $this->byHash[$client[0]] = $client[1];
        }
    }

    public static function fromConfig(): self
    {
        return new self(config('api_v2.clients'), (string) config('api_v2.client_rate_limit'));
    }

    /**
     * The client whose key is $key, or null. The key is hashed once and compared with every
     * entry's hash without stopping early, so the time taken does not depend on which entry matched.
     */
    public function find(string $key): ?Client
    {
        $presented = hash('sha256', $key);
        $found = null;
        foreach ($this->byHash as $hash => $client) {
            if (hash_equals((string) $hash, $presented)) {
                $found = $client;
            }
        }

        return $found;
    }

    /**
     * @return array [int $max, int $minutes]
     */
    public static function parseLimit(string $value, string $variable): array
    {
        $limit = self::tryParseLimit($value);
        if ($limit === null) {
            throw new ClientConfigurationException("$variable must be max,minutes with both positive integers");
        }

        return $limit;
    }

    private static function tryParseLimit(string $value): ?array
    {
        $parts = explode(',', $value);
        if (count($parts) !== 2) {
            return null;
        }

        $numbers = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if (!preg_match('/^[0-9]+$/', $part)) {
                return null;
            }
            $number = filter_var($part, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($number === false) {
                return null;
            }
            $numbers[] = $number;
        }

        return $numbers;
    }

    /**
     * @return array [string $hash, Client]
     */
    private function parseEntry(string $entry, int $position, int $defaultMax, int $defaultMinutes): array
    {
        $fields = explode(':', $entry);
        $name = $fields[0];
        $validName = preg_match(self::NAME, $name) === 1 ? $name : null;

        if (count($fields) < 2 || count($fields) > 3) {
            throw self::refusal($position, $validName, 'expected name:hash or name:hash:limit');
        }
        if ($validName === null) {
            throw self::refusal($position, null, 'name must be 1 to 40 characters of a-z, 0-9 and hyphen');
        }
        if (!preg_match(self::HASH, $fields[1])) {
            throw self::refusal($position, $validName, 'hash must be 64 lowercase hexadecimal digits');
        }

        $max = $defaultMax;
        $minutes = $defaultMinutes;
        if (count($fields) === 3) {
            $limit = self::tryParseLimit($fields[2]);
            if ($limit === null) {
                throw self::refusal($position, $validName, 'limit must be max,minutes with both positive integers');
            }
            list($max, $minutes) = $limit;
        }

        return [$fields[1], new Client($validName, $max, $minutes)];
    }

    private static function refusal(int $position, ?string $name, string $rule): ClientConfigurationException
    {
        $named = $name === null ? '' : " ($name)";

        return new ClientConfigurationException("API_V2_CLIENTS entry $position$named: $rule");
    }
}
