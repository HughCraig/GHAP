<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $lgas = DB::table('gazetteer.register')->select('lga_name')->distinct()->where('lga_name', '<>', '')->get()->toArray();
        $temp = array();
        foreach ($lgas as $row) {
            $temp[] = $row->lga_name;
        }
        $lgas = json_encode($temp, JSON_NUMERIC_CHECK);

        //feature_codes from DB
        $feature_terms = DB::table('gazetteer.register')->select('feature_term')->distinct()->where('feature_term', '<>', '')->get()->toArray();
        $temp = array();
        foreach ($feature_terms as $row) {
            $temp[] = $row->feature_term;
        }
        $feature_terms = json_encode($temp, JSON_NUMERIC_CHECK);

        //parishes from DB
        $parishes = DB::table('gazetteer.register')->select('parish')->distinct()->where('parish', '<>', '')->get()->toArray();
        $temp = array();
        foreach ($parishes as $row) {
            $temp[] = $row->parish;
        }
        $parishes = json_encode($temp, JSON_NUMERIC_CHECK);

        $states = DB::table('gazetteer.register')->select(DB::Raw('state_code'))->distinct()->orderby('state_code')->get();
        // $states = DB::table('gazetteer.register')->select('state_code')->distinct()->groupby('state_code')->get();
        $count = DB::table('gazetteer.register')->count(); //count of all register entries

        return view('ws.ghap.places.index', [
            'lgas' => $lgas,
            'feature_terms' => $feature_terms,
            'parishes' => $parishes,
            'states' => $states,
            'count' => $count,
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
