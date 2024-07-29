<?php

namespace TLCMap\Http\Controllers;

use TLCMap\Models\Dataitem;
use Illuminate\Http\Request;

class DataitemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \TLCMap\Dataitem $di
     * @return \Illuminate\Http\Response
     */
    public function show(Dataitem $di)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \TLCMap\Dataitem $di
     * @return \Illuminate\Http\Response
     */
    public function edit(Dataitem $di)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \TLCMap\Dataitem $di
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Dataitem $di)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \TLCMap\Dataitem $di
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dataitem $di)
    {
        //
    }

    /**
     * Get the IDs of all other Routes associated with the same Dataset as the given Dataitem instance,
     * excluding the Route associated with the given Dataitem instance.
     *
     * @param  integer  $dataitemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllOtherRoutesIds($dataitemId)
    {
        $dataitem = Dataitem::findOrFail($dataitemId);
        $otherRouteIds = $dataitem->allOtherRoutes()->pluck('id')->toArray();

        return response()->json($otherRouteIds);
    }

    /**
     * Get the details of the Route associated with the given Dataitem instance.
     *
     * @param  integer  $dataitemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCurrentRouteDetails($dataitemId)
    {
        $dataitem = Dataitem::findOrFail($dataitemId);
        $routeDetails = $dataitem->currentRouteDetails();

        return response()->json($routeDetails);
    }

    /**
     * Get the details of all other Routes associated with the same Dataset as the given Dataitem instance,
     * excluding the Route associated with the given Dataitem instance.
     *
     * @param  integer  $dataitemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllOtherRoutesDetails($dataitemId)
    {
        $dataitem = Dataitem::findOrFail($dataitemId);
        $otherRoutesDetails = $dataitem->allOtherRoutesDetails();

        return response()->json($otherRoutesDetails);
    }
}
