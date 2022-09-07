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

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use TLCMap\Models\Register;
use TLCMap\Models\Documentation;
use TLCMap\Models\Source;
use TLCMap\Models\Dataset;
use TLCMap\Models\Dataitem;
use Illuminate\Support\Facades\Input;
use Response;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use TLCMap\Http\Helpers\FileFormatter;
use TLCMap\Http\Helpers\SearchHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class GazetteerControllerOLD extends Controller
{
    /**
     * Direct to a message informing the user that they have more results
     *      OR results per page than the system can currently handle
     */
    public function maxPagingMessage(Request $request)
    {
        return view('ws.ghap.maxpagingmessage');
    }

    /**
     * This function stops an infinite loop between middleware and search
     *
     * The search flow is index->middleware->search->middleware->search->searchresults,
     *      as we need to kow the # of search results before we can tell the user if it is being limited
     */
    public function maxPagingRedirect(Request $request)
    {
        session(['paging.redirect.success' => 'true']);
        return redirect(session('paging.redirect.url'));
    }

    /**
     * Function for uploading a text file with a placename on each line
     * Results will be combined
     *
     * Eg: Show all with placename Newcastle, Cessnock, Shoal Bay (23 results)
     */
    public function searchFromFile(Request $request)
    {

        $bulkfile = $request->bulkfile; //Get file from request

        if (!empty($bulkfile)) {
            $results = DB::table('register'); //get Register table
            $bulkfile = $bulkfile->get();
            $delim = PHP_EOL;

            if (strpos($bulkfile, ',') !== false) { //if file contains a comma, separate by comma
                $delim = ',';
            }

            $contents = explode($delim, $bulkfile); //turn into an array - explode separating by new lines
            foreach ($contents as $line) {
                $results->orWhere('placename', '=', $line); //get the results matching this line from DB
            }

            //Get results
            $paging = 50; //???????
            $results = $results->paginate($paging)->appends(request()->query());

            return view('ws.ghap.places.show', ['details' => $results, 'query' => $results]);
        }
        return redirect()->route('index'); //if the file was empty or not attached, go back to the main page

    }

    /**
     *  Gets search results from database relevant to search query
     *  Will serve a view or downloadable object to the user depending on parameters set
     */
    public function search(Request $request, string $id = null)
    {
        /* ENV VARS */
        $MAX_PAGING = env('MAX_PAGING', false); //Load from .env   -    Any larger may cause issues
        $DEFAULT_PAGING = env('DEFAULT_PAGING', false); //if none specified
        DB::disableQueryLog(); //For large queries logging will waste precious memory - disable it

        /* Limit Paging */
        $paging = $request->input('paging');
        $format = $request->input('format');
        $download = $request->input('download');
        $sort = $request->input('sort');
        $direction = $request->input('direction');
        if (!$paging && ($format == 'html' || $format == '')) $paging = $DEFAULT_PAGING; //limit to 500 if no limit set (to speed it up)
        if (!$paging || $paging > $MAX_PAGING) $paging = $MAX_PAGING; //absolute max of 50k

        $publicdatasets = $request->input('searchpublicdatasets');
        $ausgaz = $request->input('searchausgaz');
        $dataitemid = $request->input('dataitemid');

        $asoc_array = array();
        $results = collect();

        /* if neither are selected, just do the ausgaz */
        if ($publicdatasets || $dataitemid) {
            $publicdata = $this->searchPublicDatasets($request);
            $results = $results->merge($publicdata);
        }

        if (!$dataitemid && ($ausgaz || (!$publicdatasets && !$ausgaz))) {
            $ausgaz = $this->searchAusGaz($request, $id);
            $results = $results->merge($ausgaz['results']);
            $asoc_array = $ausgaz['asoc_array'];
        }

        if ($sort && $direction) $results = ($direction == 'asc') ? $results->sortBy($sort) : $results->sortByDesc($sort);

        $results = $this->paginate($results, $paging);


        /* Outputs */
        $headers = array(); //Create an array for headers
        $filename = "tlcmap_output"; //name for the output file

        if ($format == "json") {
            if (!empty($chunks)) { //if we have sent the chunks parameter through
                return FileFormatter::jsonChunk($results, $chunks, $asoc_array); //download a zip containing the json files in chunks
            }
            if (!empty($results_collection)) $results = $results->getCollection(); //if fuzzyname, collect for json
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
            return Response::make(FileFormatter::toKML2($results, $asoc_array), '200', $headers); //serve the file to browser (or download)
        }

        //else, format as html
        return view('ws.ghap.places.showDATATABLE', ['details' => $results, 'query' => $results, 'sources' => $asoc_array]);
    }

    /**
     * Sub function for searching within the Public User Datasets
     */
    function searchPublicDatasets($request)
    {
        /* ENV VARS */
        $MAX_PAGING = env('MAX_PAGING', false); //Load from .env   -    Any larger may cause issues
        $DEFAULT_PAGING = env('DEFAULT_PAGING', false); //if none specified
        DB::disableQueryLog(); //For large queries logging will waste precious memory - disable it

        /* All Possible Parameters */
        $anps_id = $request->input('anps_id');
        $id = $anps_id; //if no id was present as a URL section, we want to use the anps_id param instead
        $from = $request->input('from');
        $to = $request->input('to');
        $lga = $request->input('lga');
        $state = $request->input('state');
        $source = $request->input('source');
        $paging = $request->input('paging');
        $name = $request->input('name');
        $fuzzyname = $request->input('fuzzyname');
        $format = $request->input('format');
        $download = $request->input('download');
        $bbox = $request->input('bbox');
        $chunks = $request->input('chunks'); //Split the output into n chunks. if n > 1 then we will have a single master file linking to n child files
        $dataitemid = $request->input('dataitemid');
        $feature_term = $request->input('feature_term');
        $parish = $request->input('parish');

        /* Bounding Box */
        if ($bbox) {
            $url = request()->fullUrl();
            $pattern = '/bbox=(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)/'; //regex to match bbox=12,13,14,15  can have negatives and 20 dec pl
            $bbox = preg_match($pattern, $url, $reg_out); //results are in indexes 1 3 5 and 7 of the $reg_out array
            if (sizeOf($reg_out) == 8 || sizeOf($reg_out) == 9) { //8 if final float has no decimals, 9 if it does
                $min_long = (float)$reg_out[1];
                $min_lat = (float)$reg_out[3];
                $max_long = (float)$reg_out[5];
                $max_lat = (float)$reg_out[7];
            } else $bbox = null;
        }

        /* Get dataitems where their parent dataset is public */
        $dataitems = Dataitem::select('placename', 'dataset_id', 'id as dataitem_id', 'description', 'latitude as TLCM_Latitude', 'longitude as TLCM_Longitude',
            'state', 'feature_term', 'source', 'lga as lga_name', 'parish', 'external_url', 'datestart', 'dateend', 'created_at', 'updated_at');

        $dataitems->whereHas('dataset', function ($q) {
            $q->where('public', '1');
        }); //dataitems where the dataset they belong to is public

        /* Building the query with the given parameters */
        if (!empty($lga)) $dataitems->where('lga', '=', $lga);
        if (!empty($name)) $dataitems->where('placename', '=', $name);
        if (!empty($dataitemid)) $dataitems->where('id', '=', $dataitemid);
        if (!empty($fuzzyname)) $dataitems->where('placename', 'LIKE', '%' . $fuzzyname . '%')->orWhere('placename', 'SOUNDS LIKE', $fuzzyname);
        if (!empty($from)) $dataitems->where('id', '>=', $from);
        if (!empty($to)) $dataitems->where('id', '<=', $to);
        if (!empty($state)) $dataitems->where('state', '=', $state);
        if ($bbox != null) {
            $dataitems->where('TLCM_Latitude', '>=', $min_lat);
            $dataitems->where('TLCM_Latitude', '<=', $max_lat);
            $dataitems->where('TLCM_Longitude', '>=', $min_long);
            $dataitems->where('TLCM_Longitude', '<=', $max_long);
        }
        if (!empty($feature_term)) $dataitems->where('feature_term', '=', $feature_term);
        if (!empty($parish)) $dataitems->where('parish', '>=', $parish);

        return $dataitems->get();
    }


    /**
     * Sub function for searching the Australian Gazetteer database
     */
    function searchAusGaz($request, $id)
    {
        /* ENV VARS */
        $MAX_PAGING = env('MAX_PAGING', false); //Load from .env   -    Any larger may cause issues
        $DEFAULT_PAGING = env('DEFAULT_PAGING', false); //if none specified
        DB::disableQueryLog(); //For large queries logging will waste precious memory - disable it

        /* DEBUG Counting time taken */
        $start_time = microtime(true); //DEBUG testing time taken

        /* All Possible Parameters */
        $anps_id = $request->input('anps_id');
        if (!$id) $id = $anps_id; //if no id was present as a URL section, we want to use the anps_id param instead
        $from = $request->input('from');
        $to = $request->input('to');
        $lga = $request->input('lga');
        $state = $request->input('state');
        $source = $request->input('source');
        $paging = $request->input('paging');
        $name = $request->input('name');
        $fuzzyname = $request->input('fuzzyname');
        $format = $request->input('format');
        $download = $request->input('download');
        $bbox = $request->input('bbox');
        $chunks = $request->input('chunks'); //Split the output into n chunks. if n > 1 then we will have a single master file linking to n child files
        $feature_term = $request->input('feature_term');
        $parish = $request->input('parish');

        /* Bounding Box */
        if (!empty($bbox)) {
            $url = request()->fullUrl();
            $pattern = '/bbox=(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)%2C(-?\d{2,3}(\.\d{0,20})?)/'; //regex to match bbox=12,13,14,15  can have negatives and 20 dec pl
            $bbox = preg_match($pattern, $url, $reg_out); //results are in indexes 1 3 5 and 7 of the $reg_out array
            if (sizeOf($reg_out) == 8 || sizeOf($reg_out) == 9) { //8 if final float has no decimals, 9 if it does
                $min_long = (float)$reg_out[1];
                $min_lat = (float)$reg_out[3];
                $max_long = (float)$reg_out[5];
                $max_lat = (float)$reg_out[7];
            } else $bbox = null;
        }

        /* DEBUG Counting time taken */
        $time_so_far = microtime(true) - $start_time;
        Log::debug('Time after bbox: ' . $time_so_far);

        /* Get Tables */
        $results = DB::table('register'); //Contains geographical places in the Gazetteer
        $doc = DB::table('documentation'); //Links places to sources
        $src = DB::table('source'); //Sources used within the gazetteer data collection

        /* Limit Paging */
        if (!$paging && ($format == 'html' || $format == '')) $paging = $DEFAULT_PAGING; //limit to 500 if no limit set (to speed it up)
        if (!$paging || $paging > $MAX_PAGING) $paging = $MAX_PAGING; //absolute max of 50k

        /* Building the query with the given parameters */
        if (!empty($id)) $results->where('anps_id', '=', $id);
        if (!empty($from)) $results->where('anps_id', '>=', $from);
        if (!empty($to)) $results->where('anps_id', '<=', $to);
        if (!empty($lga)) $results->where('lga_name', '=', $lga);
        if (!empty($state)) $results->where('state_code', '=', $state);
        if (!empty($source)) $results->where('ORIGINAL_DATA_SOURCE', '=', $source);
        if (!empty($name)) $results->where('placename', '=', $name);
        if ($bbox != null) {
            $results->where('TLCM_Latitude', '>=', $min_lat);
            $results->where('TLCM_Latitude', '<=', $max_lat);
            $results->where('TLCM_Longitude', '>=', $min_long);
            $results->where('TLCM_Longitude', '<=', $max_long);
        }
        if (!empty($fuzzyname)) $results->where('placename', 'LIKE', '%' . $fuzzyname . '%')->orWhere('placename', 'SOUNDS LIKE', $fuzzyname);
        if (!empty($feature_term)) $results->where('feature_term', '=', $feature_term);
        if (!empty($parish)) $results->where('parish', '=', $parish);

        /* DEBUG Counting time taken */
        $time_so_far = microtime(true) - $start_time;
        Log::debug('Time after Query Building ' . $time_so_far);

        /*
         *  For non-html formats we cannot 'page' the results for large queries - Instead we need to LIMIT the results
         *  We want to show the middleware warning message to inform the user that their results are being limited to the first n
         * 
         *  By putting this check BEFORE sources we are saving time by only grabbing sources on the second run through
         *  By attaching the limit BEFORE sources we are also saving time by not grabbing the sources for results that will be culled
         */
        if ($format == 'kml' || $format == 'csv' || $format == 'json') {
            if (!session()->has('paging.redirect.countchecked')) { //if we have more results than is allowed AND we have not run this block yet
                $resultscount = clone $results;
                $resultscount = count($resultscount->get()); //get the count of results without getting results

                if ($resultscount > $MAX_PAGING) {
                    session(['paging.redirect.url' => url()->full(), 'paging.redirect.countchecked' => 'true']); //set the redirect url and set countchecked to true so we do not run this block twice
                    return redirect()->route('maxPagingMessage'); //redirect to the max paging message page
                }
            }
            $results->limit($MAX_PAGING); //only get as many results as MAX_PAGING allows - n
            session()->forget('paging.redirect.countchecked'); //we have already run the above block for this query, forget this var for the next query
        }

        /* Generating source data for the results so far */
        $asoc_array = array();
        if ($format != "csv") { //we do not want the sources in the csv as this will severely increase the file size and load times

            //in mysql: SELECT DISTINCT documentation.anps_id, source.* FROM source INNER JOIN documentation ON source.source_id = documentation.doc_source_id
            $source_anps_matches = $src->join('documentation', 'source.source_id', '=', 'documentation.doc_source_id')
                ->select('documentation.anps_id', 'source.*')->distinct()->orderBy('anps_id')->get();

            //DEBUG
            $time_so_far = microtime(true) - $start_time;
            Log::debug('Time after src join ' . $time_so_far);

            //for each anps_id we have in results,
            $results->orderBy('anps_id')->chunk(50000, function ($results_chunk) use ($source_anps_matches, &$asoc_array) {
                foreach ($results_chunk as $result) {
                    if ($result->ORIGINAL_DATA_SOURCE != 'ANPS Research') continue;
                    $curr_srcs = []; //reset

                    //Get the sources for that id from the match query
                    foreach ($source_anps_matches as $match_row) {
                        if ($match_row->anps_id > $result->anps_id) break;
                        if ($match_row->anps_id == $result->anps_id) array_push($curr_srcs, $match_row);
                    }

                    $asoc_array[$result->anps_id] = $curr_srcs;
                }
            });
        }

        /* DEBUG Counting time taken */
        $time_so_far = microtime(true) - $start_time;
        Log::debug('Time after attaching sources to the data ' . $time_so_far);


        /* FUZZY NAME SORTING */
        $results_collection; //declare the var here, if it remains null then we didnt use fuzzysearch
        if ($fuzzyname) {
            $results_collection = collect($results->get());
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

        /* DEBUG Counting time taken */
        $time_so_far = microtime(true) - $start_time;
        Log::debug('Time after Fuzzy Name Sorting ' . $time_so_far);
        $resultscount = clone $results;
        $resultscount = count($resultscount->get()); //get the count of results without getting results

        /* Paginating */
        if (!empty($results_collection)) { //If we used fuzzysearch
            //$results = SearchHelper::paginate($results_collection,$paging,request()->url())->appends(request()->query()); //custom paginate function for fuzzyname
            $results = $results->get();
        } else if (!$format || ($format && ($format != 'csv' && $format != 'kml' && $format != 'json'))) { //if we have html results
            $results = $results->get();//$results = $results->paginate($paging)->appends(request()->query()); //append the pagination with the rest of the query, gets the collection from query builder
        } else $results = $results->limit($MAX_PAGING)->get(); //if we have kml csv or json results

        /* DEBUG Counting time taken */
        $time_elapsed_secs = microtime(true) - $start_time;
        Log::debug('Results: ' . count($results) . ' -  Time Taken: ' . $time_elapsed_secs);
        $query_str = '[';
        foreach (request()->query() as $key => $value) {
            $query_str .= (' ' . $key . ' => ' . $value . ',');
        }
        $query_str = substr($query_str, 0, strlen($query_str) - 1);
        $query_str .= ' ]';
        Log::debug('query: ' . $query_str);
        Log::debug('');

        return ['results' => $results, 'asoc_array' => $asoc_array];
    }

    /**
     * Paginate function that works for Collections
     * https://gist.github.com/ctf0/109d2945a9c1e7f7f7ea765a7c638db7
     */
    public function paginate($items, $perPage = 15, $page = null)
    {
        $pageName = 'page';
        $page = $page ?: (Paginator::resolveCurrentPage($pageName) ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /*  helper functions all moved to App/Http/Helpers/FileFormatter.php */

}