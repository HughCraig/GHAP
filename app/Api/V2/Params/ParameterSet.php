<?php

namespace TLCMap\Api\V2\Params;

use Illuminate\Http\Request;
use TLCMap\Api\V2\Errors\ApiException;

/**
 * Strict reader for v2 request parameters: unknown, repeated and empty
 * parameters are rejected, and typed readers validate values.
 */
class ParameterSet
{
    /** @var array<string,string> */
    private $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromRequest(Request $request, array $allowed): self
    {
        $counts = self::countNames((string) $request->server('QUERY_STRING', ''));
        $values = [];

        foreach ($request->query->all() as $name => $value) {
            $name = (string) $name;
            if (!in_array($name, $allowed, true)) {
                throw ApiException::invalidParameter(
                    $name,
                    'unknown',
                    implode(', ', $allowed),
                    "The parameter '$name' is not recognised."
                );
            }
            if (is_array($value) || ($counts[$name] ?? 1) > 1) {
                throw ApiException::invalidParameter(
                    $name,
                    'repeated',
                    'a single value',
                    "The parameter '$name' must appear only once and must not be an array."
                );
            }
            if ($value === null || $value === '') {
                throw ApiException::invalidParameter(
                    $name,
                    'empty',
                    'a non-empty value',
                    "The parameter '$name' must not be empty."
                );
            }
            $values[$name] = (string) $value;
        }

        return new self($values);
    }

    /** Count each parameter name in the raw query string, normalised the way PHP does. */
    private static function countNames(string $queryString): array
    {
        $counts = [];
        if ($queryString === '') {
            return $counts;
        }
        foreach (explode('&', $queryString) as $part) {
            if ($part === '') {
                continue;
            }
            $name = urldecode(explode('=', $part, 2)[0]);
            $name = preg_replace('/\[.*$/s', '', $name);
            $name = str_replace(['.', ' '], '_', $name);
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }
        return $counts;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    public function raw(string $name): ?string
    {
        return $this->values[$name] ?? null;
    }

    public function all(): array
    {
        return $this->values;
    }

    public function string(string $name, ?int $maxLength = null): ?string
    {
        if (!$this->has($name)) {
            return null;
        }
        $max = $maxLength ?? (int) config('api_v2.string_max');
        $value = $this->values[$name];
        if (mb_strlen($value, 'UTF-8') > $max) {
            throw ApiException::invalidParameter(
                $name,
                'max_length',
                "at most $max characters",
                "The parameter '$name' must be at most $max characters long."
            );
        }
        return $value;
    }

    public function bool(string $name, bool $default): bool
    {
        if (!$this->has($name)) {
            return $default;
        }
        $value = $this->values[$name];
        if ($value === 'true') {
            return true;
        }
        if ($value === 'false') {
            return false;
        }
        throw ApiException::invalidParameter(
            $name,
            'boolean',
            'true, false',
            "The parameter '$name' must be true or false."
        );
    }

    public function int(string $name, int $min, int $max, ?int $default): ?int
    {
        if (!$this->has($name)) {
            return $default;
        }
        $value = $this->values[$name];
        $accepts = $min . '–' . $max;
        if (!preg_match('/^-?[0-9]+$/D', $value)) {
            throw ApiException::invalidParameter(
                $name,
                'integer',
                $accepts,
                "The parameter '$name' must be a whole number."
            );
        }
        $negative = $value[0] === '-';
        $digits = ltrim($negative ? substr($value, 1) : $value, '0');
        // 18 digits always fit in a 64-bit integer; anything longer is out of range.
        $int = null;
        if (strlen($digits) <= 18) {
            $int = (int) $value;
        }
        if ($int === null || $int < $min || $int > $max) {
            throw ApiException::invalidParameter(
                $name,
                'range',
                $accepts,
                "The parameter '$name' must be between $min and $max."
            );
        }
        return $int;
    }

    public function enum(string $name, array $values, ?string $default): ?string
    {
        if (!$this->has($name)) {
            return $default;
        }
        $value = $this->values[$name];
        if (!in_array($value, $values, true)) {
            $accepts = implode(', ', $values);
            throw ApiException::invalidParameter(
                $name,
                'one_of',
                $accepts,
                "The parameter '$name' must be one of: $accepts."
            );
        }
        return $value;
    }
}
