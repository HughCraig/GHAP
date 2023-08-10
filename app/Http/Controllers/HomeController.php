<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TLCMap\Models\Dataitem;
use TLCMap\Models\Datasource;
use TLCMap\Models\RecordType;

class HomeController extends Controller
{
    /**
     * Loads up the home page featuring a search bar, etc
     * Pre-loads all the LGA names, states, and count for use in the form (states dropdown, LGA autocomplete, etc)
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {

        //lgas from DB
        $lgas = json_encode(Dataitem::getAllLga(), JSON_NUMERIC_CHECK);

        //feature_codes from DB
        $feature_terms = json_encode(Dataitem::getAllFeatures(), JSON_NUMERIC_CHECK);

        //parishes from DB
        $parishes = json_encode(Dataitem::getAllParishes(), JSON_NUMERIC_CHECK);

        //record types from DB (place type)
        $recordtypes = RecordType::all();

        $states = Dataitem::getAllStates();

        $count = Dataitem::count(); //count of all register entries

        $datasources = Datasource::all();

        return view('ws.ghap.places.index', [
            'lgas' => $lgas,
            'feature_terms' => $feature_terms,
            'parishes' => $parishes,
            'recordtypes' => $recordtypes, 
            'states' => $states,
            'count' => $count,
            'datasources' => $datasources,
        ]);
    }

    /**
     * About page.
     */
    public function aboutPage()
    {
        return view('ws.ghap.about');
    }
}
