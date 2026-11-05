<?php

namespace Tests\V2\Support;

/**
 * Proleptic Gregorian calendar arithmetic, independent of PostgreSQL.
 */
class Civil
{
    /**
     * Epoch milliseconds of 00:00:00 UTC on the given date (astronomical year numbering).
     */
    public static function udate(int $astronomicalYear, int $month = 1, int $day = 1): int
    {
        $y = $astronomicalYear - ($month <= 2 ? 1 : 0);
        $era = intdiv($y - ($y < 0 ? 399 : 0), 400);
        $yoe = $y - $era * 400;
        $doy = intdiv(153 * ($month + ($month > 2 ? -3 : 9)) + 2, 5) + $day - 1;
        $doe = $yoe * 365 + intdiv($yoe, 4) - intdiv($yoe, 100) + $doy;
        $days = $era * 146097 + $doe - 719468;

        return $days * 86400000;
    }
}
