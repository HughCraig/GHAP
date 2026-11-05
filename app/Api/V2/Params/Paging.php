<?php

namespace TLCMap\Api\V2\Params;

use Illuminate\Http\Request;

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

    /**
     * The full URL of the next page (spec §5.3): this request with `cursor` set, every other
     * parameter kept. Null on the last page.
     */
    public static function nextUrl(Request $request, ?string $cursor): ?string
    {
        if ($cursor === null) {
            return null;
        }
        $query = $request->query->all();
        $query['cursor'] = $cursor;

        return $request->url() . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }
}
