<?php

namespace TLCMap\Api\V2\Params;

use TLCMap\Api\V2\Errors\ApiException;

/** A pseudo-random sample of the match set: its size and the seed that fixes it. */
class Sample
{
    private const CONFLICTS = ['sort', 'direction', 'nulls', 'limit', 'cursor'];

    /** @var int */
    public $size;
    /** @var string */
    public $seed;

    private function __construct(int $size, string $seed)
    {
        $this->size = $size;
        $this->seed = $seed;
    }

    /** Null when no `sample` is given. */
    public static function parse(ParameterSet $p, int $max): ?self
    {
        if (!$p->has('sample')) {
            if ($p->has('seed')) {
                throw ApiException::invalidParameter(
                    'seed',
                    'requires',
                    'sample',
                    "The parameter 'seed' can only be given together with 'sample'."
                );
            }
            return null;
        }

        $size = $p->int('sample', 1, $max, null);

        foreach (self::CONFLICTS as $name) {
            if ($p->has($name)) {
                throw ApiException::invalidParameter(
                    'sample',
                    'conflicts',
                    'not combined with sort, direction, nulls, limit or cursor',
                    "The parameter 'sample' cannot be combined with '$name'."
                );
            }
        }

        $seed = $p->string('seed', (int) config('api_v2.seed_max'));

        return new self($size, $seed ?? bin2hex(random_bytes(8)));
    }
}
