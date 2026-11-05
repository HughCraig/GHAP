<?php

namespace TLCMap\Api\V2\Params;

use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Errors\ApiException;

/** A date bound as text plus its start and end in epoch milliseconds. */
class DateBound
{
    /** @var string */
    public $text;
    /** @var int */
    public $start;
    /** @var int */
    public $end;

    private function __construct(string $text, int $start, int $end)
    {
        $this->text = $text;
        $this->start = $start;
        $this->end = $end;
    }

    public static function parse(string $parameter, string $value): self
    {
        $row = DB::selectOne(
            'select tlcmap.v2_udate(?) as s, tlcmap.v2_udate_end(?) as e',
            [$value, $value]
        );
        if ($row === null || $row->s === null || $row->e === null) {
            throw ApiException::invalidParameter(
                $parameter,
                'date',
                'a year or date such as 1820, 1820-03 or 1820-03-15; negative years are BCE',
                "The parameter '$parameter' is not a date TLCMap can read."
            );
        }
        return new self($value, (int) $row->s, (int) $row->e);
    }
}
