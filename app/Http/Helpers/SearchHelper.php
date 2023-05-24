<?php
/*
 * Benjamin McDonnell
 * For TLCMap project, University of Newcastle
 * 
 * Some helper methods for the RegisterController
 * Relating to fuzzy search
 */

namespace TLCMap\Http\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Response;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchHelper
{
    public static function sortBySimilarity($fuzzyname, $string_collection)
    {

    }

    public static function paginate($items, $perPage, $baseUrl = null, $page = null, $options = [])
    {
        /*
            https://laracasts.com/discuss/channels/laravel/how-to-paginate-laravel-collection
            https://arjunphp.com/laravel-5-pagination-array/
	 */

        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        $lap = new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
        if ($baseUrl) {
            $lap->setPath($baseUrl);
        }
        return $lap;

    }
}
