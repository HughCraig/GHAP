<?php

namespace TLCMap\Http\Controllers;

use TLCMap\Models\Dataitem;
use TLCMap\Models\Dataset;
use TLCMap\Models\Datasource;
use TLCMap\Models\RecordType;
use Illuminate\Support\Facades\Auth;

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

        $layers = json_encode(Dataset::getAllPublicLayersAndIDs());

        //parishes from DB
        $parishes = json_encode(Dataitem::getAllParishes(), JSON_NUMERIC_CHECK);

        //record types from DB (place type)
        $recordtypes = RecordType::all();

        $recordTypeMap = RecordType::getIdTypeMap();

        $states = Dataitem::getAllStates();

        $count = Dataitem::count(); //count of all register entries

        $datasources = Datasource::all();

        $userLayers = [];
        if (Auth::check()) {
            $userDatasets = Auth::user()->datasets()->get();
            if ($userDatasets->count() > 0) {
                foreach ($userDatasets as $dataset) {
                    $userLayers[] = [
                        'id' => $dataset->id,
                        'name' => $dataset->name,
                    ];
                }
                // Sort by name.
                usort($userLayers, function ($a, $b) {
                    return strcmp($a['name'], $b['name']);
                });
            }
        }

        return view('ws.ghap.places.index', [
            'lgas' => $lgas,
            'feature_terms' => $feature_terms,
            'parishes' => $parishes,
            'recordtypes' => $recordtypes,
            'states' => $states,
            'count' => $count,
            'datasources' => $datasources,
            'layers' => $layers,
            'userLayers' => $userLayers,
            'recordTypeMap' => $recordTypeMap
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
