<?php

namespace TLCMap\Http\Controllers;

use TLCMap\Models\Dataitem;
use TLCMap\Models\Dataset;
use TLCMap\Models\Datasource;
use TLCMap\Models\RecordType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    /**
     * Loads up the home page featuring a search bar, etc
     * Pre-loads all the LGA names, states, and count for use in the form (states dropdown, LGA autocomplete, etc)
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {

        if ($request->has('format')) {
            if (
                $request->input('format') == 'json' ||
                $request->input('format') == 'csv' ||
                $request->input('format') == 'kml'
            ) {

                return (new GazetteerController())->search($request);
            }
        }
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
                        'is_public' => $dataset->public
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

    public function json()
    {
        $data = [
            'title' => 'TLCMap Web Services API',
            'description' => 'Access information in Time Layered Cultural Map (TLCMap) and Gazetteer of Historical Australian Places (GHAP) using the web services API. While we encourage open data, terms and conditions are specific to each layer or multilayer uploaded by the TLCMap user. Please check the licensing and terms and conditions on each layer you access before re-using. You can run sophisticated searches or list layers or multilayers to access the information in each layer. For documentation, see https://tlcmap.org.',
            'endpoints' => [
                [
                    'title' => 'Layers',
                    'url' => 'https://tlcmap.org/layers/json',
                    'description' => 'List all TLCMap layers in JSON format. Change "json" for "csv" or "kml" to receive the data in those formats. Each layer includes many places.'
                ],
                [
                    'title' => 'Multilayers',
                    'url' => 'https://tlcmap.org/multilayers/json',
                    'description' => 'List all TLCMap multilayers in JSON format. Change "json" for "csv" or "kml" to receive the data in those formats. The multilayer will list the layers it contains.'
                ],
                [
                    'title' => 'Search',
                    'url' => 'https://tlcmap.org/?searchpublicdatasets=on&searchausgaz=on&searchncg=on&containsname=Newcastle&format=json',
                    'description' => 'A sophisticated search of all places using GET parameters. This URL is an example of a search for "Newcastle". The format is determined by the &format parameter, which may be csv, kml, or json. For full details on GET parameters, see TLCMap documentation at tlcmap.org.'
                ]
            ]
        ];

        return response()->json($data);
    }

    public function kml()
    {
        $data = <<<KML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
    <Document>
        <name>TLCMap Web Services API</name>
        <description>Access information in Time Layered Cultural Map (TLCMap) and Gazetteer of Historical Australian Places (GHAP) using the web services API. While we encourage open data, terms and conditions are specific to each layer or multilayer uploaded by the TLCMap user. Please check the licensing and terms and conditions on each layer you access before re-using.</description>
        
        <Placemark>
            <name>Layers</name>
            <description>List all TLCMap layers as json. Change 'json' for 'csv' or 'kml'. Each layer includes many places.</description>
            <ExtendedData>
                <Data name="URL">
                    <value>https://tlcmap.org/layers/json</value>
                </Data>
            </ExtendedData>
        </Placemark>
        
        <Placemark>
            <name>Multilayers</name>
            <description>List all TLCMap multilayers as json. Change 'json' for 'csv' or 'kml'. The multilayer will list the layers it contains.</description>
            <ExtendedData>
                <Data name="URL">
                    <value>https://tlcmap.org/multilayers/json</value>
                </Data>
            </ExtendedData>
        </Placemark>
        
        <Placemark>
            <name>Search</name>
            <description>A sophisticated search of all places using GET parameters. This URL is an example of a search for 'Newcastle'.</description>
            <ExtendedData>
                <Data name="URL">
                    <value>https://tlcmap.org/?searchpublicdatasets=on&searchausgaz=on&searchncg=on&containsname=Newcastle&format=json</value>
                </Data>
            </ExtendedData>
        </Placemark>
        
    </Document>
</kml>
KML;

        return response($data, 200)
            ->header('Content-Type', 'application/vnd.google-earth.kml+xml');
    }

    public function csv()
    {
        $csvContent = "Title, Description\n";
        $csvContent .= "TLCMap Web Services API, Access information in Time Layered Cultural Map (TLCMap) and Gazetteer of Historical Australian Places (GHAP) using the web services API. While we encourage open data, terms and conditions are specific to each layer or multilayer uploaded by the TLCMap user. Please check the licensing and terms and conditions on each layer you access before re-using. You can run sophisticated searches or list layers or multilayers to access the information in each layer. For documentation, see https://tlcmap.org.\n\n";

        // Endpoints
        $csvContent .= "Title, URL, Description\n";
        $csvContent .= "Layers, https://tlcmap.org/layers/json, List all TLCMap layers in JSON format. Change 'json' for 'csv' or 'kml' to receive the data in those formats. Each layer includes many places.\n";
        $csvContent .= "Multilayers, https://tlcmap.org/multilayers/json, List all TLCMap multilayers in JSON format. Change 'json' for 'csv' or 'kml' to receive the data in those formats. The multilayer will list the layers it contains.\n";
        $csvContent .= "Search, https://tlcmap.org/?searchpublicdatasets=on&searchausgaz=on&searchncg=on&containsname=Newcastle&format=json, A sophisticated search of all places using GET parameters. This URL is an example of a search for 'Newcastle'.\n";

        return response($csvContent, 200)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="tlcmap_api_documentation.csv"');
    }
}
