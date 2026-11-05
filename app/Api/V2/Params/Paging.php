<?php

namespace TLCMap\Api\V2\Params;

/** Page size, the cursor as given, and the optional cap on rows across pages. */
class Paging
{
    /** @var int */
    public $perPage;
    /** @var string|null */
    public $cursor;
    /** @var int|null */
    public $limit;

    private function __construct(int $perPage, ?string $cursor, ?int $limit)
    {
        $this->perPage = $perPage;
        $this->cursor = $cursor;
        $this->limit = $limit;
    }

    public static function parse(ParameterSet $p, int $default, int $max): self
    {
        return new self(
            $p->int('per_page', 1, $max, $default),
            $p->raw('cursor'),
            $p->int('limit', 1, PHP_INT_MAX, null)
        );
    }
}
