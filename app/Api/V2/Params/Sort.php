<?php

namespace TLCMap\Api\V2\Params;

/** The order of a list: the sort key's name, its direction, and where null keys go. */
class Sort
{
    public const DIRECTIONS = ['asc', 'desc'];
    public const NULLS = ['first', 'last'];

    /** @var string */
    public $key;
    /** @var string */
    public $direction;
    /** @var string */
    public $nulls;

    private function __construct(string $key, string $direction, string $nulls)
    {
        $this->key = $key;
        $this->direction = $direction;
        $this->nulls = $nulls;
    }

    /** @param string[] $keys the endpoint's accepted `sort` values */
    public static function parse(ParameterSet $p, array $keys, string $default): self
    {
        return new self(
            $p->enum('sort', $keys, $default),
            $p->enum('direction', self::DIRECTIONS, 'asc'),
            $p->enum('nulls', self::NULLS, 'last')
        );
    }

    public function toEcho(): array
    {
        return ['sort' => $this->key, 'direction' => $this->direction, 'nulls' => $this->nulls];
    }
}
