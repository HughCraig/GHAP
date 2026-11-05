<?php

namespace TLCMap\Api\V2\Query;

use Illuminate\Support\Facades\DB;
use TLCMap\Api\V2\Errors\ApiException;

/** Checks requested layers exist and are public (spec §7.4). */
class LayerGate
{
    /**
     * One query; the first offending id, in the order given, is reported.
     *
     * @param int[] $ids
     */
    public static function assertPublic(array $ids): void
    {
        if (!$ids) {
            return;
        }
        $public = [];
        foreach (DB::table('tlcmap.dataset')->whereIn('id', $ids)->get(['id', 'public']) as $row) {
            $public[(int) $row->id] = $row->public === true;
        }
        foreach ($ids as $id) {
            if (!array_key_exists($id, $public)) {
                throw ApiException::layerNotFound($id);
            }
            if (!$public[$id]) {
                throw ApiException::layerPrivate($id);
            }
        }
    }
}
