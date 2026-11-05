<?php

namespace Tests\V2\Unit;

use Tests\V2\TestCase;
use TLCMap\Api\V2\Errors\ApiException;
use TLCMap\Api\V2\Params\Cursor;

class CursorTest extends TestCase
{
    private const HASH = '0123456789abcdef';

    private static function raw(array $payload): string
    {
        return self::b64(json_encode($payload));
    }

    private static function b64(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    private static function payload(array $overrides = []): array
    {
        return array_merge(['v' => 1, 'h' => self::HASH, 'k' => 5, 'i' => 900001, 'n' => 4], $overrides);
    }

    private function assertRejected(string $value, string $keyType = 'int', string $hash = self::HASH): void
    {
        try {
            Cursor::decode($value, $hash, $keyType);
        } catch (ApiException $e) {
            $this->assertSame(400, $e->status());
            $this->assertSame('INVALID_PARAMETER', $e->errorCode());
            $this->assertSame('cursor', $e->details()['parameter']);
            $this->assertSame('invalid', $e->details()['rule']);
            $this->assertSame('a cursor returned by a previous page of this query', $e->details()['accepts']);
            return;
        }
        $this->fail('Expected ApiException for cursor ' . $value);
    }

    public function testRoundTripIntKey(): void
    {
        $value = Cursor::encode(self::HASH, 42, 900007, 12);
        $this->assertRegExp('/^[A-Za-z0-9_-]+$/D', $value);

        $c = Cursor::decode($value, self::HASH, 'int');
        $this->assertSame(42, $c->lastKey);
        $this->assertSame(900007, $c->lastId);
        $this->assertSame(12, $c->served);

        $this->assertSame(42, Cursor::decode($value, self::HASH, 'nullable_int')->lastKey);
    }

    public function testRoundTripStringKeyWithQuoteAndUnicode(): void
    {
        $key = "O'Connell — Ngun’gara \"Ōtautahi\" /x\\";
        $value = Cursor::encode(self::HASH, $key, 900002, 0);
        $this->assertRegExp('/^[A-Za-z0-9_-]+$/D', $value);

        $c = Cursor::decode($value, self::HASH, 'string');
        $this->assertSame($key, $c->lastKey);
        $this->assertSame(900002, $c->lastId);
        $this->assertSame(0, $c->served);

        $this->assertSame('', Cursor::decode(Cursor::encode(self::HASH, '', 1, 1), self::HASH, 'string')->lastKey);
        $this->assertSame($key, Cursor::decode($value, self::HASH, 'nullable_string')->lastKey);
    }

    public function testRoundTripNullKey(): void
    {
        $value = Cursor::encode(self::HASH, null, 900003, 8);
        $this->assertNull(Cursor::decode($value, self::HASH, 'nullable_int')->lastKey);
        $this->assertNull(Cursor::decode($value, self::HASH, 'nullable_string')->lastKey);
        $this->assertSame(900003, Cursor::decode($value, self::HASH, 'nullable_int')->lastId);
        $this->assertSame(8, Cursor::decode($value, self::HASH, 'nullable_int')->served);
    }

    public function testEncodingIsTheDocumentedJson(): void
    {
        $value = Cursor::encode(self::HASH, 'a/b', 7, 3);
        $json = base64_decode(strtr($value, '-_', '+/'));
        $this->assertSame('{"v":1,"h":"0123456789abcdef","k":"a/b","i":7,"n":3}', $json);
        $this->assertStringNotContainsString('=', $value);
    }

    public function testDecodeToleratesMissingPadding(): void
    {
        // Lengths that need one and two padding characters both decode.
        foreach ([900001, 9000011, 90000111] as $id) {
            $value = Cursor::encode(self::HASH, 1, $id, 0);
            $this->assertSame($id, Cursor::decode($value, self::HASH, 'int')->lastId);
        }
    }

    public function testRejectsNotBase64(): void
    {
        $this->assertRejected('not base64!');
        $this->assertRejected('abc$def');
        $this->assertRejected(strtr(self::raw(self::payload()), '-_', '+/') . '+/');
        $this->assertRejected(self::raw(self::payload()) . '=');
        $this->assertRejected('A');
        $this->assertRejected('');
    }

    public function testRejectsBase64OfNonJson(): void
    {
        $this->assertRejected(self::b64('hello world'));
        $this->assertRejected(self::b64('{"v":1,'));
        $this->assertRejected(self::b64("\xff\xfe\x00"));
    }

    public function testRejectsNonObjectJson(): void
    {
        $this->assertRejected(self::b64('[1,"0123456789abcdef",5,900001,4]'));
        $this->assertRejected(self::b64('1'));
        $this->assertRejected(self::b64('"x"'));
        $this->assertRejected(self::b64('null'));
    }

    public function testRejectsWrongVersion(): void
    {
        $this->assertRejected(self::raw(self::payload(['v' => 2])));
        $this->assertRejected(self::raw(self::payload(['v' => '1'])));
    }

    public function testRejectsWrongHash(): void
    {
        $this->assertRejected(self::raw(self::payload()), 'int', 'fedcba9876543210');
        $this->assertRejected(self::raw(self::payload(['h' => 123])));
    }

    public function testRejectsBadId(): void
    {
        $this->assertRejected(self::raw(self::payload(['i' => '1; drop table x'])));
        $this->assertRejected(self::raw(self::payload(['i' => '900001'])));
        $this->assertRejected(self::raw(self::payload(['i' => 1.5])));
        $this->assertRejected(self::raw(self::payload(['i' => null])));
        // An integer too large for PHP decodes as a float.
        $this->assertRejected(self::b64('{"v":1,"h":"0123456789abcdef","k":5,"i":99999999999999999999,"n":4}'));
    }

    public function testRejectsKeyOfTheWrongType(): void
    {
        $this->assertRejected(self::raw(self::payload(['k' => [1]])), 'int');
        $this->assertRejected(self::raw(self::payload(['k' => 'abc'])), 'int');
        $this->assertRejected(self::raw(self::payload(['k' => '5'])), 'int');
        $this->assertRejected(self::raw(self::payload(['k' => 5.5])), 'int');
        $this->assertRejected(self::raw(self::payload(['k' => null])), 'int');
        $this->assertRejected(self::raw(self::payload(['k' => 5])), 'string');
        $this->assertRejected(self::raw(self::payload(['k' => null])), 'string');
        $this->assertRejected(self::raw(self::payload(['k' => ['a' => 1]])), 'nullable_string');
        $this->assertRejected(self::raw(self::payload(['k' => 'abc'])), 'nullable_int');
        $this->assertRejected(self::raw(self::payload(['k' => true])), 'nullable_int');
    }

    public function testRejectsNegativeOrNonIntegerServed(): void
    {
        $this->assertRejected(self::raw(self::payload(['n' => -1])));
        $this->assertRejected(self::raw(self::payload(['n' => '4'])));
        $this->assertRejected(self::b64('{"v":1,"h":"0123456789abcdef","k":5,"i":900001,"n":4.0}'));
    }

    public function testRejectsMissingOrExtraFields(): void
    {
        foreach (['v', 'h', 'k', 'i', 'n'] as $field) {
            $p = self::payload();
            unset($p[$field]);
            $this->assertRejected(self::raw($p));
        }
        $this->assertRejected(self::raw(self::payload(['x' => 1])));
    }

    public function testRejectsAnUnknownKeyType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Cursor::decode(Cursor::encode(self::HASH, 1, 1, 0), self::HASH, 'float');
    }

    public function testQueryHashIgnoresCursorAndPerPageAndOrder(): void
    {
        $a = Cursor::queryHash(['sort' => 'title', 'name' => 'Newcastle']);
        $this->assertRegExp('/^[0-9a-f]{16}$/D', $a);
        $this->assertSame($a, Cursor::queryHash(['name' => 'Newcastle', 'sort' => 'title']));
        $this->assertSame($a, Cursor::queryHash(['name' => 'Newcastle', 'sort' => 'title', 'cursor' => 'x', 'per_page' => '4']));
        $this->assertNotSame($a, Cursor::queryHash(['name' => 'Newcastle', 'sort' => 'id']));
        $this->assertNotSame($a, Cursor::queryHash(['name' => 'Newcastle', 'sort' => 'title', 'limit' => '10']));

        $expected = substr(hash('sha256', json_encode(
            ['name' => 'Ngun’gara/x', 'sort' => 'title'],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        )), 0, 16);
        $this->assertSame($expected, Cursor::queryHash(['sort' => 'title', 'name' => 'Ngun’gara/x', 'cursor' => 'c']));
    }
}
