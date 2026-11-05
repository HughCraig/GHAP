<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;

/**
 * The opaque paging cursor: base64url (no padding) of
 * {"v":1,"h":query hash,"k":last key,"i":last id,"n":rows served}.
 * Decoding is strict, so nothing but well-typed values ever reaches a query.
 */
class Cursor
{
    public const VERSION = 1;
    public const KEY_TYPES = ['int', 'string', 'nullable_int', 'nullable_string'];

    private const FIELDS = ['h', 'i', 'k', 'n', 'v'];
    private const JSON_FLAGS = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /** @var int|string|null */
    public $lastKey;
    /** @var int */
    public $lastId;
    /** @var int */
    public $served;

    private function __construct($lastKey, int $lastId, int $served)
    {
        $this->lastKey = $lastKey;
        $this->lastId = $lastId;
        $this->served = $served;
    }

    /** The hash binding a cursor to its query: every parameter except `cursor` and `per_page`. */
    public static function queryHash(array $params): string
    {
        unset($params['cursor'], $params['per_page']);
        ksort($params);

        return substr(hash('sha256', json_encode($params, self::JSON_FLAGS | JSON_THROW_ON_ERROR)), 0, 16);
    }

    /** @param int|string|null $lastKey */
    public static function encode(string $hash, $lastKey, int $lastId, int $served): string
    {
        $json = json_encode(
            ['v' => self::VERSION, 'h' => $hash, 'k' => $lastKey, 'i' => $lastId, 'n' => $served],
            self::JSON_FLAGS | JSON_THROW_ON_ERROR
        );

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public static function decode(string $value, string $expectedHash, string $keyType): self
    {
        if (!in_array($keyType, self::KEY_TYPES, true)) {
            throw new \InvalidArgumentException("Unknown cursor key type '$keyType'.");
        }

        // The base64url alphabet only, without padding; a length of 1 mod 4 is never valid.
        if (!preg_match('/^[A-Za-z0-9_-]+$/D', $value) || strlen($value) % 4 === 1) {
            throw self::invalid();
        }
        $json = base64_decode(strtr($value, '-_', '+/'), true);
        if ($json === false) {
            throw self::invalid();
        }

        $data = json_decode($json, true, 4);
        if (!is_array($data)) {
            throw self::invalid();
        }
        $fields = array_map('strval', array_keys($data));
        sort($fields, SORT_STRING);
        if ($fields !== self::FIELDS) {
            throw self::invalid();
        }

        if ($data['v'] !== self::VERSION
            || !is_string($data['h']) || $data['h'] !== $expectedHash
            || !is_int($data['i'])
            || !is_int($data['n']) || $data['n'] < 0
            || !self::keyMatches($data['k'], $keyType)) {
            throw self::invalid();
        }

        return new self($data['k'], $data['i'], $data['n']);
    }

    private static function keyMatches($key, string $keyType): bool
    {
        if ($key === null) {
            return strpos($keyType, 'nullable_') === 0;
        }
        $base = str_replace('nullable_', '', $keyType);

        return $base === 'int' ? is_int($key) : is_string($key);
    }

    private static function invalid(): ApiException
    {
        return ApiException::invalidParameter(
            'cursor',
            'invalid',
            'a cursor returned by a previous page of this query',
            "The parameter 'cursor' is not a cursor returned by a previous page of this query."
        );
    }
}
