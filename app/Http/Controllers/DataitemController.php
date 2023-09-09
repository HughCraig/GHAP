<?php

namespace TLCMap\Http\Controllers;

use Response;
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
     * View a public data item as JSON
     * Calls private function to generate JSON for a data item
     * 
     * @param string $id uid for the data item
     * @return Response with JSON datatype, or redirect to public layers page if not found
     */
    public function viewPublicJSON(string $id)
    {
        $dataitem = Dataitem::searchScope()->where('uid', $id)
            ->first();
        if (!isset($dataitem)) {
            return redirect()->route('layers');
        }
        return Response::make($dataitem->json(), 200, ['Content-Type' => 'application/json']);
    }

    /**
     * View a public data item as CSV
     * Calls private function to generate CSV for a data item
     * 
     * @param string $id uid for the data item
     * @return Response with CSV datatype, or redirect to public layers page if not found
     */
    public function viewPublicCSV(string $id)
    {
        $dataitem = Dataitem::searchScope()->where('uid', $id)
            ->first();
        if (!isset($dataitem)) {
            return redirect()->route('layers');
        }
        return Response::make($dataitem->csv(), '200', array('Content-Type' => 'text/csv')); 
    }

    /**
     * View a public data item as KML
     * Calls private function to generate KML for a data item
     * 
     * @param string $id
     * @return Response with KML datatype, or redirect to public layers page if not found
     */
    public function viewPublicKML(string $id)
    {
        $dataitem = Dataitem::searchScope()->where('uid', $id)
            ->first();
        if (!isset($dataitem)) {
            return redirect()->route('layers');
        }
        return Response::make($dataitem->kml(), '200', array('Content-Type' => 'text/xml'));
    }
}
