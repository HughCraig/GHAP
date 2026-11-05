<?php

use Illuminate\Support\Facades\Route;

/*
| v2 routes. Every route is registered through this helper, which adds a matching OPTIONS route for
| the CORS preflight (204). A catch-all OPTIONS route is deliberately not used: it would make
| Laravel answer 405 instead of 404 for unknown paths.
|
| Routes to come (Tasks 13-15), the four of spec section 3.1:
|   GET /api/v2/places                  PlaceController@search
|   GET /api/v2/layers                  LayerController@index
|   GET /api/v2/layers/{id}             LayerController@show      ({id} digits only)
|   GET /api/v2/layers/{id}/features    LayerController@features  ({id} digits only)
*/
$v2get = function (string $uri, $action) {
    Route::options($uri, function () {
        return response('', 204);
    });

    return Route::get($uri, $action);
};
