<?php

use Illuminate\Support\Facades\Route;

/*
| v2 routes. Every route is registered through this helper, which adds a matching OPTIONS route for
| the CORS preflight (204). A catch-all OPTIONS route is deliberately not used: it would make
| Laravel answer 405 instead of 404 for unknown paths.
|
| Routes, the four of spec section 3.1:
|   GET /api/v2/places                  PlaceController@search
|   GET /api/v2/layers                  LayerController@index
|   GET /api/v2/layers/{id}             LayerController@show      ({id} 1 to 18 digits)
|   GET /api/v2/layers/{id}/features    LayerController@features  ({id} 1 to 18 digits)
|
| Eighteen digits always fit in a 64-bit integer; a longer id names no layer, and is a 404.
*/
$v2get = function (string $uri, $action, array $where = []) {
    Route::options($uri, function () {
        return response('', 204);
    })->where($where);

    return Route::get($uri, $action)->where($where);
};

$v2get('places', 'PlaceController@search');
$v2get('layers', 'LayerController@index');
$v2get('layers/{id}', 'LayerController@show', ['id' => '[0-9]{1,18}']);
$v2get('layers/{id}/features', 'LayerController@features', ['id' => '[0-9]{1,18}']);
