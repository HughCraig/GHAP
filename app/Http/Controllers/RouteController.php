<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\SavedSearch;
use TLCMap\Models\Dataitem;
use TLCMap\Models\Dataset;
use TLCMap\Models\RecordType;
use TLCMap\Models\Route;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use TLCMap\Http\Helpers\GeneralFunctions;

class RouteController extends Controller
{
    public function getAllStopIndices($routeId)
    {
        $route = Route::findOrFail($routeId);
        $dataitemIDsAndStopIndices = $route->allStopIndices();
        return response()->json($dataitemIDsAndStopIndices);
    }
}
