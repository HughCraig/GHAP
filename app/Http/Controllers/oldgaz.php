<?php
/*
 * Benjamin McDonnell
 * For TLCMap project, University of Newcastle
 * 
 * The primary controller for this application
 * Handles the logic related to querying the tlcmap database
 * index displays the main page with the search form, POSTS data through about the lga, state, and count
 * search handles the search query and displays the results page with the applied filters
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Register;
use App\Models\Documentation;
use App\Models\Source;
use Illuminate\Support\Facades\Input;
use Response;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Helpers\FileFormatter;
use App\Http\Helpers\SearchHelper;
use Illuminate\Pagination\Paginator;

class RegisterController extends Controller
{

    public function index()
        /*
         * Loads up the home page featuring a search bar, etc
         * Pre-loads all the LGA names, states, and count for use in the form (states dropdown, LGA autocomplete, etc)
         */
    {
        $register = DB::table('register'); //get table
        $lgas = $register->select('lga_name')->distinct()->where('lga_name', '<>', '')->get()->toArray();
        $temp = array();
        foreach ($lgas as $row) {
            $temp[] = $row->lga_name;
        }
        $lgas = json_encode($temp, JSON_NUMERIC_CHECK);

        $states = $register->select('state_code')->distinct()->groupby('state_code')->get();
        $count = $register->count(); //count of all register entries

        return view('ws.ghap.places.index', ['lgas' => $lgas, 'states' => $states, 'count' => $count]);
    }

    public function searchFromFile(Request $request)
    {
        /*
         *  Function for uploading a text file with a placename on each line
         *  Results will be combined
         *  Eg: Show all with placename Newcastle, Cessnock, Shoal Bay (23 results)
         *  TODO: Test this function
         */
        $bulkfile = $request->bulkfile; //Get file from request
        if (!empty($bulkfile)) {
            $results = DB::table('register'); //get table
            $contents = explode(PHP_EOL, $bulkfile->get()); //turn into an array
            foreach ($contents as $line) {
                $results->orWhere('placename', '=', $line); //get the results matching this line from DB
            }

            //Get results
            $paging = 50;
            $results = $results->paginate($paging)->appends(request()->query()); //should be 4 results

            return view('ws.ghap.places.show', ['details' => $results, 'query' => $results]);
        }
        return redirect()->route('index'); //if the file was empty or not attached, go back to the main page

    }

    public function search(string $id = null)
    {
        /*
         *  Function returned when the web.php Route file points to RegisterController@search
         *  Gets info from database relevant to search query
         *  Will serve a view or downloadable object to the user depending on parameters set
         */

        //All possible parameters, if they exist
        $anps_id = Input::get('anps_id');
        $from = Input::get('from');
        $to = Input::get('to');;
        $lga = Input::get('lga');
        $state = Input::get('state');
        $source = Input::get('source');
        $paging = Input::get('paging');
        $name = Input::get('name');
        $fuzzyname = Input::get('fuzzyname');
        $format = Input::get('format');
        $download = Input::get('download');
        $bbox = Input::get('bbox');
        $chunks = Input::get('chunks'); //Split the output into n chunks. if n > 1 then we will have a single master file linking to n child files

        //handling bbox
        if (!empty($bbox)) {
            $url = request()->fullUrl();
            $pattern = '/bbox=(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)/'; //regex to match bbox=12,13,14,15  can have negatives and 20 dec pl
            $bbox = preg_match($pattern, $url, $reg_out); //results are in indexes 1 3 5 and 7 of the $reg_out array
            if (sizeOf($reg_out) == 8) {
                $min_long = (float)$reg_out[1];
                $min_lat = (float)$reg_out[3];
                $max_long = (float)$reg_out[5];
                $max_lat = (float)$reg_out[7];
            } else $bbox = null;
        }

        //Default variables
        if (!$id) $id = $anps_id; //if no id was there, we want to find the anps_id instead

        //$result = Result::get(); //select all
        $register = DB::table('register');
        $doc = DB::table('documentation');
        $src = DB::table('source');

        $results = clone $register; //copy of register table

        if (!$paging) $paging = 500; //limit to 500 if no limit set (to speed it up)

        //building the query with conditionals - Will only add to the query if the parameters are present in the GET request
        if (!empty($id)) $results->where('anps_id', '=', $id);
        if (!empty($from)) $results->where('anps_id', '>=', $from);
        if (!empty($to)) $results->where('anps_id', '<=', $to);
        if (!empty($lga)) $results->where('lga_name', '=', $lga);
        if (!empty($state)) $results->where('state_code', '=', $state);
        if (!empty($source)) $results->where('ORIGINAL_DATA_SOURCE', '=', $source);
        if (!empty($name)) $results->where('placename', '=', $name);
        if (!empty($bbox)) {
            $results->where('TLCM_Latitude', '>=', $min_lat);
            $results->where('TLCM_Latitude', '<=', $max_lat);
            $results->where('TLCM_Longitude', '>=', $min_long);
            $results->where('TLCM_Longitude', '<=', $max_long);
        }
        $results_collection;
        if (!empty($fuzzyname)) {
            //Union of SOUNDS LIKE and LIKE %name% results
            //Order the results of that query by sounds_like distance to the original input
            $like = clone $results;
            $like->where('placename', 'LIKE', '%' . $fuzzyname . '%'); //Get the results containing fuzzyname
            $results->where('placename', 'SOUNDS LIKE', $fuzzyname); //Get the results that sound like fuzzyname
            $results->union($like); //union them
            $results_collection = collect($results->get()); //turn into collection (necessary?)

            //similar_text sort by placename closest to fuzzyname
            $results_collection = $results_collection->sort(function ($a, $b) use ($fuzzyname) {
                //order by:
                //equals fuzzyname
                if (strtolower($a->placename) == strtolower($fuzzyname) && strtolower($b->placename) != strtolower($fuzzyname)) return -1;
                if (strtolower($b->placename) == strtolower($fuzzyname) && strtolower($a->placename) != strtolower($fuzzyname)) return 1;
                //else startswith fuzzyname
                if (strpos(strtolower($a->placename), strtolower($fuzzyname)) === 0 && strpos(strtolower($b->placename), strtolower($fuzzyname)) !== 0) return -1;
                if (strpos(strtolower($b->placename), strtolower($fuzzyname)) === 0 && strpos(strtolower($a->placename), strtolower($fuzzyname)) !== 0) return 1;
                //else contains fuzzname
                if (strpos(strtolower($a->placename), strtolower($fuzzyname)) !== false && strpos(strtolower($b->placename), strtolower($fuzzyname)) === false) return -1;
                if (strpos(strtolower($b->placename), strtolower($fuzzyname)) !== false && strpos(strtolower($a->placename), strtolower($fuzzyname)) === false) return 1;
                //else similar_text ranking to fuzzyname
                $al = similar_text($fuzzyname, $a->placename);
                $bl = similar_text($fuzzyname, $b->placename);
                return $al === $bl ? 0 : ($al > $bl ? -1 : 1);
            });

        }

        /*
            Paginating
         */
        if (!empty($results_collection)) {
            $results = SearchHelper::paginate($results_collection, $paging, request()->url())->appends(request()->query()); //custom paginate function
        } else {
            $results = $results->paginate($paging)->appends(request()->query()); //append the pagination with the rest of the query, gets the collection from query builder

        }

        $sources; //defining vars for the if statement
        $asoc_array = array();

        /*
         *  Attaching source data to the collection
         */
        if ($format != "csv") { //we do not want the sources in the csv as this will severely increase the file size and load times
            //query for all distinct matches between source.source_id and documentation.doc_source_id
            //in mysql: SELECT DISTINCT documentation.anps_id, source.* FROM source INNER JOIN documentation ON source.source_id = documentation.doc_source_id
            $source_anps_matches = $src->join('documentation', 'source.source_id', '=', 'documentation.doc_source_id')->select('documentation.anps_id', 'source.*')->distinct()->get(); //TODO: make the sources a separate function?

            //for each anps_id we have in results,
            foreach ($results as $result) {
                //Get the sources for that id from the match query
                $sources = $source_anps_matches->where('anps_id', $result->anps_id);
                //push it into array for this key
                $asoc_array[$result->anps_id] = $sources;
                //$result->sources = $sources;
            }
        }


        $headers = array(); //Create an array for headers
        $filename = "tlcmap_output"; //name for the output file

        //Formats
        if ($format == "json") {
            if (!empty($chunks)) { //if we have sent the chunks parameter through
                return FileFormatter::jsonChunk($results, $chunks, $asoc_array); //download a zip containing the json files in chunks
            }
            $headers['Content-Type'] = 'application/json'; //set header content type
            if (!empty($download)) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.json"'; //if we are downloading, add a 'download attachment' header
            return Response::make(FileFormatter::toGeoJSON($results, $asoc_array), '200', $headers); //serve the file to browser (or download)
        }
        if ($format == "csv") {
            $headers["Content-Type"] = "text/csv"; //set header content type
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.csv"'; //always download csv
            return FileFormatter::toCSV($results, $headers); //Download
        }
        if ($format == "kml") {
            if (!empty($chunks)) { //if we have sent the chunks parameter through
                return FileFormatter::kmlChunk($results, $chunks, $asoc_array); //download a zip contianing n chunks
            }
            $headers['Content-Type'] = 'text/xml'; //set header content type
            if (!empty($download)) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.kml"'; //if we are downloading, add a 'download attachment' header
            return Response::make(FileFormatter::toKML($results, $asoc_array), '200', $headers); //serve the file to browser (or download)
        }

        //else, format as html
        //return view('ws.ghap.places.show')->withDetails($results)->withQuery("test")->with('sources',$asoc_array);
        return view('ws.ghap.places.show', ['details' => $results, 'query' => $results, 'sources' => $asoc_array]);
    }

    /*
     *  helper functions all moved to App/Http/Helpers/FileFormatter.php
     */

}