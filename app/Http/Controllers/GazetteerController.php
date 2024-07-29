<?php
/*
 * System Architect: Bill Pascoe
 * Main developer: Benjamin McDonnell (2020), Bill Pascoe (2021 upgrade)
 * For TLCMap project, University of Newcastle
 *
 * The primary controller for this application
 * Handles the logic related to querying the tlcmap database
 * index displays the main page with the search form, POSTS data through about the lga, state, and count
 * search handles the search query and displays the results page with the applied filters
 */

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use TLCMap\Http\Helpers\UID;
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
use Carbon\Carbon;

use TLCMap\Http\Helpers\GeneralFunctions;
use TLCMap\Models\Datasource;
use TLCMap\ROCrate\ROCrateGenerator;

use TLCMap\Models\RecordType;
use TLCMap\Models\Route;

class GazetteerController extends Controller
{
    /********************/
    /* PUBLIC FUNCTIONS */
    /********************/

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

    public function bulkFileParser(Request $request)
    {
        $bulkfile = $request->file->get(); //get contents of file
        $names = str_replace(PHP_EOL, ',', $bulkfile); //replace all NEWLINES with commas

        //trim edges and replace extra spaces with a single space
        $names = explode(',', $names);
        foreach ($names as &$name) {
            $name = trim($name);
            $name = preg_replace('/\s+/', ' ', $name); //replace any instance of multiple spaces with a single space
        }

        return response()->json(['names' => join(",", $names)]); //join back into a single comma separated string
    }

    function kmlPolygonPlaceToPoints($place)
    {
        if (empty($place->Polygon)) return null;
        //"lon,lat,alt lon,lat,alt" OR "lon,lat,alt\nlon,lat,alt" allowed
        //$trimmed = trim(str_replace(" ", "", $place->Polygon->outerBoundaryIs->LinearRing->coordinates));
        $points = array_filter(preg_split('/[\s]+/', $place->Polygon->outerBoundaryIs->LinearRing->coordinates)); //split around space or newline //old:  //explode("\n",$trimmed);
        foreach ($points as &$point) {
            $point = explode(",", $point);
        }
        return $points; //array of points where point is an array of long,lat,alt OR long,lat
    }

    function polygonPointsArrayToSQL($points)
    {
        //array of points where point is an array of long,lat,alt OR long,lat
        //POLYGON((lng1 lat1, lng2 lat2, lngn latn, lng1 lat1))
        $str = "POLYGON((";
        if (end($points) != reset($points)) array_push($points, reset($points)); //if final point does not equal first point, add first point as final point
        foreach ($points as $point) {
            $str .= $point[0] . " " . $point[1] . ", ";
        }
        $str = substr($str, 0, strlen($str) - 2); //strip final comma
        $str .= "))";
        return $str;
    }

    public function searchFromKmlPolygon(Request $request)
    {
        $file = $request->polygonkml;
        if (empty($file)) return redirect()->route('index'); //if the file was empty or not attached, go back to the main page

        // This currently only search ANPS data. Maybe better to give users the option to select the datasource.
        $results = Datasource::anps()->dataitems();

        //Go through the kml and find Polygons
        $data = array();
        $xml_object = simplexml_load_file($file);
        $polygons = [];

        if (!empty($xml_object->Document->Folder)) {
            foreach ($xml_object->Document->Folder as $folder) { //Each layer is a Folder
                foreach ($folder->Placemark as $place) { //each Point, Polygon, or LineString is a Placemark
                    $points = $this->kmlPolygonPlaceToPoints($place);
                    if ($points) array_push($polygons, $this->polygonPointsArrayToSQL($points));
                }
            }
        } else if (!empty($xml_object->Document->Placemark)) { //we must also support cases where there is no Folder, just Placemarks within Document
            foreach ($xml_object->Document->Placemark as $place) {
                if (!empty($place->Polygon)) { //if place is a polygon
                    $points = $this->kmlPolygonPlaceToPoints($place);
                    if ($points) array_push($polygons, $this->polygonPointsArrayToSQL($points));
                }
            }
        } else return redirect()->route('index'); //file does not contain a polygon

        if (empty($polygons)) return redirect()->route('index'); //Could not extract a valid polygon

        //for each Polygon generate a raw SQL style polygon
        $results->where(function ($query) use ($polygons) {
            for ($i = 0; $i < count($polygons); $i++) {
                $query->OrWhereRaw("ST_CONTAINS(ST_GEOMFROMTEXT('" . $polygons[$i] . "'), ST_POINT(longitude::double precision,latitude::double precision) )");
            }
        });

        //sql: WHERE ST_CONTAINS(ST_GEOMFROMTEXT('POLYGON((lng1 lat1, lng2 lat2, lngn latn, lng1 lat1))'), POINT(longitude,latitude) )
        //limit results to just those within any one of these polygons

        //Get results
        $paging = 50; //???????
        $results = $results->paginate($paging)->appends(request()->query()); //paginate

        return view('ws.ghap.places.show', ['details' => $results, 'query' => $results]);
    }

    /**
     *  Gets search results from database relevant to search query
     *  Will serve a view or downloadable object to the user depending on parameters set
     */
    public function search(Request $request, string $uid = null, string $format = null)
    {
        if ($request->has('id')) {
            // Single place . Redirect to route places/{id}/{format?}'
            $redirectUrl = '/places/' . $request->input('id');
            if ($request->has('format')) {
                $redirectUrl .= '/' . $request->input('format');
            }
            return redirect()->to($redirectUrl);
        }

        if (isset($uid) && $request->has('format')) {
            //Redirect to route places/{uid}/{format?}
            $redirectUrl = '/places/' . $uid   . '/' . $request->input('format');

            //Redirect to route places/{uid}/{format}?download=on
            if ($request->has('download') && $request->input('download') === 'on') {
                $redirectUrl .= '?download=on';
            }

            return redirect()->to($redirectUrl);
        }


        /* ENV VARS */
        $MAX_PAGING = config('app.maxpaging');     //should move the rest of the direct env calls to be config calls but this will take forever
        $DEFAULT_PAGING = config('app.defaultpaging');

        /* PARAMETERS */
        $parameters = $this->getParameters($request->all());
        if (isset($uid)) {
            $parameters['id'] = $uid;
        }
        if (isset($format)) {
            $parameters['format'] = $format;
        }

        // Set redirect URL for new home page
        if (empty($parameters['format'])) {

            if ($uid) {
                $redirectUrl = '/?gotoid=' . $uid;
            } else {
                $queryString = http_build_query($request->query());
                $redirectUrl = '/' . ($queryString ? '?' . $queryString : '');
            }

            return redirect($redirectUrl);
        }


        if ($parameters['names']) $parameters['names'] = array_map('trim', explode(',', $parameters['names']));
        if ($parameters['fuzzynames']) $parameters['fuzzynames'] = array_map('trim', explode(',', $parameters['fuzzynames']));
        if ($parameters['containsnames']) $parameters['containsnames'] = array_map('trim', explode(',', $parameters['containsnames']));

        /* LIMIT PAGING */
        $paging = $parameters['paging'];
        if (!$paging && ($parameters['format'] == '') || !$parameters['format'] || $parameters['format'] == 'html') $paging = $DEFAULT_PAGING; //limit to DEFAULT if no limit set (to speed it up)
        if (!$paging || $paging > $MAX_PAGING) $paging = $MAX_PAGING; //limit to MAX if over max

        // Search dataitems.
        $results = $this->searchDataitems($parameters);

        /* MAX SIZE CHECK */
        /*
        Ivy's note
            Attention!!
            We are using $MAX_PAGING to check the maximum size of searched results for downloading and mapping!
            That's why FileFormatter::toGeoJSON() doesn't have process for paginated results.
        */
        $reachMaximumSize = $this->maxSizeCheck($results, $parameters['format'], $MAX_PAGING);
        if ($reachMaximumSize) {
            if ($parameters['mapping'] === null) {
                return redirect()->route('maxPagingMessage'); //if results > $MAX_PAGING show warning msg
            } else {
                $this->maxPagingRedirect($request);
            }
        }

        /* FUZZY NAME SORTING - skip if table sorting is applied */
        if ($parameters['fuzzyname'] && (!$parameters['sort'] || !$parameters['direction'])) $results = $this->fuzzysort($results, $parameters['fuzzyname'], true); //last param true if fuzzy, false if contains
        else if ($parameters['containsname'] && (!$parameters['sort'] || !$parameters['direction'])) $results = $this->fuzzysort($results, $parameters['containsname'], false);

        /* SORT AND DIRECTION */
        if ($parameters['sort'] && $parameters['direction']) $results = ($parameters['direction'] == 'asc') ? $results->sortBy($parameters['sort']) : $results->sortByDesc($parameters['sort']);

        /***
         * COLLECT ROUTES
         * This section is only executed under specific conditions:
         * 1. When the output format is JSON AND
         * 2. When mapping is enabled AND
         * 3. When mobility mapping is requested AND
         * 4. When the maximum allowable mapping volume of searched data items includes routes
         *
         * Purpose:
         * - To process and include route information for mobility mapping
         * - Limited to the system's maximum allowed mapping quantity of searched data items
         * - Ensures efficient handling of large datasets by processing only the necessary items
         ***/
        $routes = null;
        if ($parameters['format'] == "json" && $parameters['mapping'] && $parameters['mobility'] && !empty($this->hasMobInfo) && $this->hasMobInfo['hasrouteid']) {
            $routes = collect();
            $limitedResults = $results->take($MAX_PAGING);
            $displayMode = $parameters["mobility"];

            if ($displayMode !== 'route') {
                $limitedResults = $this->addTimeStopIndex($limitedResults, $displayMode);
            }
            $routes = $this->processRoutes($limitedResults, $displayMode);
        }

        /* APPLY PAGING/LIMITING */
        $results = $this->paginate($results, $paging); //Turn into a LengthAwarePaginator - APPLIES LIMITING!

        /* OUTPUT */
        return $this->outputs($parameters, $results, $routes);
    }

    /********************/
    /* SEARCH FUNCTIONS */
    /********************/

    public static function searchDataitems($parameters)
    {
        $gazetteerController = new GazetteerController();

        // Get datasource IDs to search.
        $datasourceIDs = [];
        $allDatasourceIDs = [];
        $datasources = Datasource::all();
        foreach ($datasources as $datasource) {
            if (isset($parameters[$datasource->search_param_name])) {
                $datasourceIDs[] = $datasource->id;
            }
            $allDatasourceIDs[] = $datasource->id;
        }
        // Set to all datasources if no parameter specified.
        if (empty($datasourceIDs)) {
            $datasourceIDs = $allDatasourceIDs;
        }
        // Search dataitems.
        $dataitems = Dataitem::searchScope()
            ->with(['dataset' => function ($q) {
                $q->select('id', 'name', 'warning');
            }])
            ->with(['datasource' => function ($q) {
                $q->select('id', 'name', 'description', 'link');
            }])
            ->whereIn('datasource_id', $datasourceIDs);

        /* GET BBOX PARAMS */
        $bbox = isset($parameters['bbox']) ? $gazetteerController->getBbox($parameters['bbox']) : null;
        $polygon = isset($parameters['polygon']) ? $gazetteerController->getPolygon($parameters['polygon']) : null;

        // Search UID.
        if (isset($parameters['id'])) {
            $dataitems->where('uid', '=', $parameters['id']);
        }

        // Legacy support of ANPS ID search.
        if (isset($parameters['anps_id'])) {
            $anpsSource = Datasource::anps();
            if ($anpsSource) {
                $searchAnpsID = $parameters['anps_id'];
                // Apply a logic group for the convenience of further logics.
                $dataitems->where(function ($query) use ($anpsSource, $searchAnpsID) {
                    $query->where('datasource_id', $anpsSource->id)
                        ->where('original_id', $searchAnpsID);
                });
            }
        }

        if (isset($parameters['names']) && $parameters['names']) {
            $names = array_filter(array_map('trim', $parameters['names']));
        } elseif (isset($parameters['fuzzynames']) && $parameters['fuzzynames']) {
            $names = array_filter(array_map('trim', $parameters['fuzzynames']));
        } elseif (isset($parameters['containsnames']) && $parameters['containsnames']) {
            $names = array_filter(array_map('trim', $parameters['containsnames']));
        } else {
            $names = null;
        }

        if ($names) { //if we are bulk searching from file
            if (!empty($names)) { //if we dont have an empty array
                //If we are bulk searching from file, skip name and fuzzyname search and search from file instead
                if (isset($parameters['names']) && $parameters['names']) {
                    $dataitems->where(function ($query) use ($names, $parameters) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('title', 'ILIKE', $firstcase)->orWhere('placename', 'ILIKE', $firstcase);
                        foreach ($names as $line) {
                            $query->orWhere('title', 'ILIKE', $line)->orWhere('placename', 'ILIKE', $firstcase);
                        };
                        if ($parameters['searchdescription'] === 'on') {
                            $query->orWhere('description', 'ILIKE', '%' . $parameters['name'] . '%');
                        }
                    });
                } else if (isset($parameters['fuzzynames']) && $parameters['fuzzynames']) {
                    $dataitems->where(function ($query) use ($names, $parameters) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('title', 'ILIKE', '%' . $firstcase . '%')->orWhereRaw('placename % ?', $firstcase);
                        //$query->where('placename', 'ILIKE', '%'.$firstcase.'%')->orWhere('placename', 'SOUNDS LIKE', $firstcase);
                        foreach ($names as $line) {
                            $query->orWhere('title', 'ILIKE', '%' . $line . '%')->orWhereRaw('placename % ?', $line);
                        };
                        if ($parameters['searchdescription'] === 'on') {
                            $query->orWhere('description', 'ILIKE', '%' . $parameters['fuzzyname'] . '%');
                        }
                    });
                } else if (isset($parameters['containsnames']) && $parameters['containsnames']) {
                    $dataitems->where(function ($query) use ($names, $parameters) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('title', 'ILIKE', '%' . $firstcase . '%')->orWhere('placename', 'ILIKE', '%' . $firstcase . '%');
                        foreach ($names as $line) {
                            $query->orWhere('title', 'ILIKE', '%' . trim($line) . '%')->orWhere('placename', 'ILIKE', '%' . trim($line) . '%');
                        }
                        if ($parameters['searchdescription'] === 'on') {
                            $query->orWhere('description', 'ILIKE', '%' . $parameters['containsname'] . '%');
                        }
                    });
                }
            } else $dataitems->where('title', '=', null); //we did a bulk search but all of the names equated to empty strings! Show no results
        } else {
            if (isset($parameters['name']) && $parameters['name']) {
                $dataitems->where(function ($query) use ($parameters) {
                    $query->where('title', 'ILIKE', $parameters['name']);
                    if ($parameters['searchdescription'] === 'on') {
                        $query->orWhere('description', 'ILIKE', '%' . $parameters['name'] . '%');
                    }
                });
            } else if (isset($parameters['fuzzyname']) && $parameters['fuzzyname']) {
                $dataitems->where(function ($query) use ($parameters) {
                    $query->where('title', 'ILIKE', '%' . $parameters['fuzzyname'] . '%')->orWhereRaw('title % ?', $parameters['fuzzyname'])
                        ->orWhere('placename', 'ILIKE', '%' . $parameters['fuzzyname'] . '%')->orWhereRaw('placename % ?', $parameters['fuzzyname']);
                    //$query->where('placename', 'ILIKE', '%'.$parameters['fuzzyname'].'%')->orWhere('placename', 'SOUNDS LIKE', $parameters['fuzzyname']);
                    if ($parameters['searchdescription'] === 'on') {
                        $query->orWhere('description', 'ILIKE', '%' . $parameters['fuzzyname'] . '%');
                    }
                });
            } else if (isset($parameters['containsname']) && $parameters['containsname']) {
                $dataitems->where(function ($query) use ($parameters) {
                    $query->where('title', 'ILIKE', '%' . $parameters['containsname'] . '%')->orWhere('placename', 'ILIKE', '%' . $parameters['containsname'] . '%');
                    if ($parameters['searchdescription'] === 'on') {
                        $query->orWhere('description', 'ILIKE', '%' . $parameters['containsname'] . '%');
                    }
                });
            }
        }

        /* BUILD SEARCH QUERY WITH PARAMS */
        if (isset($parameters['recordtype']) && $parameters['recordtype']) {
            $dataitems->whereHas('recordtype', function ($query) use ($parameters) {
                $query->where('type', '=', $parameters['recordtype']); // Filter by recordtype value
            });
        }
        if (isset($parameters['searchlayers'])) {
            $searchLayerIDs = explode(',', $parameters['searchlayers']);

            $dataitems->whereHas('dataset', function ($query) use ($searchLayerIDs) {
                $query->whereIn('dataset_id', $searchLayerIDs);
            });
        }
        if (isset($parameters['extended_data'])) {
            //Extended data
            $extendedDataQueries = explode('AND', $parameters['extended_data']);

            // List of allowed conditions
            $allowed_conditions = ['textmatch', '>', '<', '=', 'before', 'after'];
            $pattern = '/\s(' . implode('|', array_map('preg_quote', $allowed_conditions)) . ')\s/';

            foreach ($extendedDataQueries as $extendedDataQuery) {

                $attribute = '';
                $condition = '';
                $value = '';

                if (preg_match($pattern, $extendedDataQuery, $matches)) {
                    $condition = trim($matches[1]);

                    $parts = preg_split($pattern, $extendedDataQuery);

                    if (count($parts) === 2) {
                        // Trim and remove quotes
                        $attribute = trim($parts[0], " '\"");
                        $value = trim($parts[1], " '\"");
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }

                //Sanitize attribute
                if (!preg_match('/^[\w\s]+$/', $attribute)) {
                    continue;
                }
                $xpath_query = "//Data[@name=\"$attribute\"]/value";

                switch (strtolower($condition)) {
                    case 'textmatch':
                        //Parameterized Queries
                        $dataitems->whereNotNull('extended_data')
                            ->whereRaw('LENGTH(extended_data) > 0')
                            ->whereRaw("(xpath('string($xpath_query)', extended_data::xml))[1]::text ILIKE ?", ["%$value%"]);
                        break;
                    case '>':
                        if (is_numeric($value)) {
                            $dataitems->whereNotNull('extended_data')
                                ->whereRaw('LENGTH(extended_data) > 0')
                                ->whereRaw("CASE
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\d+$'
                                                    THEN (xpath('string($xpath_query)', extended_data::xml))[1]::text::integer > $value
                                                    ELSE false
                                                END");
                        }
                        break;
                    case '<':
                        if (is_numeric($value)) {
                            $dataitems->whereNotNull('extended_data')
                                ->whereRaw('LENGTH(extended_data) > 0')
                                ->whereRaw("CASE
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\d+$'
                                                    THEN (xpath('string($xpath_query)', extended_data::xml))[1]::text::integer < $value
                                                    ELSE false
                                                END");
                        }
                        break;
                    case '=':
                        //Parameterized Queries
                        $dataitems->whereNotNull('extended_data')
                            ->whereRaw('LENGTH(extended_data) > 0')
                            ->whereRaw("(xpath('string($xpath_query)', extended_data::xml))[1]::text = ?", [$value]);
                        break;
                    case 'before':
                        //Supported date format : yyyy-mm-dd   yyyy-mm   yyyy
                        if (preg_match('/^(\d{4}-\d{2}-\d{2}|\d{4}-\d{2}|\d{4})$/', $value)) {
                            $dataitems->whereNotNull('extended_data')
                                ->whereRaw('LENGTH(extended_data) > 0');

                            $dataitems->whereRaw("
                                                CASE
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\\d{4}-\\\\d{2}-\\\\d{2}$'
                                                        THEN TO_TIMESTAMP((xpath('string($xpath_query)', extended_data::xml))[1]::text, 'YYYY-MM-DD') < TO_TIMESTAMP('$value', 'YYYY-MM-DD')
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\\d{4}-\\\\d{2}$'
                                                        THEN TO_TIMESTAMP((xpath('string($xpath_query)', extended_data::xml))[1]::text || '-01', 'YYYY-MM-DD') < TO_TIMESTAMP('$value', 'YYYY-MM-DD')
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\\d{4}$'
                                                        THEN TO_TIMESTAMP((xpath('string($xpath_query)', extended_data::xml))[1]::text || '-01-01', 'YYYY-MM-DD') < TO_TIMESTAMP('$value', 'YYYY-MM-DD')
                                                    ELSE false
                                                END = true");
                        }
                        break;
                    case 'after':
                        //Supported date format : yyyy-mm-dd   yyyy-mm   yyyy
                        if (preg_match('/^(\d{4}-\d{2}-\d{2}|\d{4}-\d{2}|\d{4})$/', $value)) {
                            $dataitems->whereNotNull('extended_data')
                                ->whereRaw('LENGTH(extended_data) > 0');

                            $dataitems->whereRaw("
                                                CASE
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\\d{4}-\\\\d{2}-\\\\d{2}$'
                                                        THEN TO_TIMESTAMP((xpath('string($xpath_query)', extended_data::xml))[1]::text, 'YYYY-MM-DD') > TO_TIMESTAMP('$value', 'YYYY-MM-DD')
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\\d{4}-\\\\d{2}$'
                                                        THEN TO_TIMESTAMP((xpath('string($xpath_query)', extended_data::xml))[1]::text || '-01', 'YYYY-MM-DD') > TO_TIMESTAMP('$value', 'YYYY-MM-DD')
                                                    WHEN (xpath('string($xpath_query)', extended_data::xml))[1]::text ~ E'^\\\\d{4}$'
                                                        THEN TO_TIMESTAMP((xpath('string($xpath_query)', extended_data::xml))[1]::text || '-01-01', 'YYYY-MM-DD') > TO_TIMESTAMP('$value', 'YYYY-MM-DD')
                                                    ELSE false
                                                END = true");
                        }
                        break;
                }
            }
        }
        if (isset($parameters['lga'])) $dataitems->where('lga', '=', $parameters['lga']);
        if (isset($parameters['dataitemid'])) $dataitems->where('id', '=', $parameters['dataitemid']);
        if (isset($parameters['from'])) $dataitems->where('id', '>=', $parameters['from']);
        if (isset($parameters['to'])) $dataitems->where('id', '<=', $parameters['to']);
        if (isset($parameters['state'])) $dataitems->where('state', '=', $parameters['state']);
        if (isset($parameters['feature_term'])) {
            $searchTerms = explode(';', $parameters['feature_term']);
            $dataitems->wherein('feature_term', $searchTerms);
        }

        if ($bbox) {
            $dataitems->where('latitude', '>=', $bbox['min_lat']);
            $dataitems->where('latitude', '<=', $bbox['max_lat']);

            if ($bbox['min_long'] <= $bbox['max_long']) { //if min is lower than max we have not crossed the 180th meridian
                $dataitems->where('longitude', '>=', $bbox['min_long']);
                $dataitems->where('longitude', '<=', $bbox['max_long']);
            } else { //else we have crossed the 180th meridian
                $dataitems->where(function ($query) use ($bbox) {
                    $query->where('longitude', '>=', $bbox['min_long'])->orWhere('longitude', '<=', $bbox['max_long']);
                });
            }
        }
        if ($polygon) { //sql: WHERE ST_CONTAINS(ST_GEOMFROMTEXT('POLYGON((lng1 lat1, lng2 lat2, lngn latn, lng1 lat1))'), POINT(longitude,latitude) )
            $polygonsql = "ST_GEOMFROMTEXT('POLYGON((";
            for ($i = 0; $i < count($polygon); $i += 2) { //for each point
                $polygonsql .= $polygon[$i] . " "; //long
                $polygonsql .= $polygon[$i + 1] . ", "; //lat
            }
            $polygonsql = substr($polygonsql, 0, strlen($polygonsql) - 2); //strip final comma
            $polygonsql .= "))')";
            $dataitems->where(function ($query) use ($polygonsql) {
                $query->whereRaw("ST_CONTAINS(" . $polygonsql . ", ST_POINT(longitude,latitude) )")->orWhereRaw("ST_CONTAINS(" . $polygonsql . ", ST_POINT(longitude+360,latitude) )")
                    ->orWhereRaw("ST_CONTAINS(" . $polygonsql . ", ST_POINT(longitude-360,latitude) )");
            });
        }
        if (isset($parameters['subquery'])) {
            $dataitems = $gazetteerController->diSubquery($dataitems, $parameters);
        }

        if (isset($parameters['sort'])  ||  (isset($parameters['line']) && $parameters['line'] === 'time')) {

            $dataitems = Dataset::infillDataitemDates($dataitems);
            $dataitems = $dataitems->where('datestart', '!=', '')->where('dateend', '!=', '');

            if ($parameters["sort"] === 'end') {
                $dataitems = $dataitems->orderBy('dateend');
            } else {
                $dataitems = $dataitems->orderBy('datestart');
            }
        }

        // Fetch associated route information if the dataitem has any
        $dataitems = $dataitems->withStopIdxForSearchedDataitems();

        /*
        MOBILITY DATA CHECKING - whether the returned dataitems having mobility-related attributes
                Step 1: Collect qualified mobility datatiem IDs
                        As searched dataitems mapping using download URL, we need to update $hasMobInfo that is used for mobility mapping
                        according to maximum showing number of results. (Pairing with MAX SIZE CHECK in this->search())
        */
        $hasMobilityInfo = [
            'default' => [],
            'hasquantity' => [],
            'hasrouteid' => [],
            'hasrouteiddatestart' => [],
            'hasrouteiddateend' => []
        ];
        $MAX_PAGING = config('app.maxpaging');

        $mobilityDataitems = (clone $dataitems->getQuery())
            ->select('*')
            ->limit($MAX_PAGING)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('tlcmap.recordtype')
                    ->whereColumn('recordtype.id', 'tlcmap.dataitem.recordtype_id')
                    ->where('recordtype.type', 'Mobility');
            });
        $mobilityCollection = $mobilityDataitems->get();

        foreach ($mobilityCollection as $item) {
            $hasMobilityInfo['default'][] = $item->id;

            if (isset($item->quantity)) {
                $hasMobilityInfo['hasquantity'][] = $item->id;
            }

            if (isset($item->route_id)) {
                $hasMobilityInfo['hasrouteid'][] = $item->id;

                if (isset($item->datestart)) {
                    $hasMobilityInfo['hasrouteiddatestart'][] = $item->id;
                }

                if (isset($item->dateend)) {
                    $hasMobilityInfo['hasrouteiddateend'][] = $item->id;
                }
            }
        }

        $collection = $dataitems->get(); //needs to be applied a second time for some reason (maybe because of the subquery?)

        //Modifying the collection directly, as datestart and dateend fields are TEXT fields not dates, simpler this way (might be a little slower)
        if (isset($parameters['dateto']) || isset($parameters['datefrom'])) {
            if ($mobilityCollection->isEmpty()) {
                $collection = $collection->filter(function ($v) use ($parameters, $gazetteerController) {
                    return $gazetteerController->dateSearch(
                        $parameters['datefrom'] ?? null,
                        $parameters['dateto'] ?? null,
                        $v->datestart,
                        $v->dateend
                    );
                });
            } else {
                /*
                MOBILITY DATA CHECKING - whether the returned dataitems having mobility-related attributes
                        Step 2: Collect filtered datatiem IDs in dateSearch
                */
                $excludedIds = [];
                $collection = $collection->filter(function ($v) use ($parameters, $that, &$excludedIds) {
                    $isIncluded = $that::dateSearch($parameters['datefrom'], $parameters['dateto'], $v->datestart, $v->dateend);
                    if (!$isIncluded) {
                        $excludedIds[] = $v->id;
                    }
                    return $isIncluded;
                });
                // Exclude filtered dataitem IDs in $hasMobilityInfo
                foreach ($hasMobilityInfo as $key => $ids) {
                    $hasMobilityInfo[$key] = array_diff($ids, $excludedIds);
                }
            }
        }

        /*
        MOBILITY DATA CHECKING - whether the returned dataitems having mobility-related attributes
                Step 3: Check final qualified datatiem IDs and store
        */
        foreach ($hasMobilityInfo as $key => $ids) {
            $hasMobilityInfo[$key] = !empty($ids);
        }

        $this->hasMobInfo = $hasMobilityInfo;

        return $collection;
    }

    /********************/
    /* HELPER FUNCTIONS */
    /********************/
    function circleWrapString($circle, $latCol, $long)
    {
        return "111.1111 " .
            "* DEGREES(ACOS(COS(RADIANS(" . $circle['lat'] . ")) " .
            "* COS(RADIANS(" . $latCol . ")) " .
            "* COS(RADIANS(" . $circle['long'] . ") - RADIANS(" . $long . ")) " .
            "+ SIN(RADIANS(" . $circle['lat'] . ")) " .
            "* SIN(RADIANS(" . $latCol . ")))) " .
            "<= " . $circle['rad'] / 1000;
    }

    function fuzzysort($collection, $fuzzyname, $isFuzzy)
    {
        return $collection->sort(function ($a, $b) use ($fuzzyname, $isFuzzy) {
            //if two entries are exactly identical, the order does not matter
            if (strtolower($a->placename) == strtolower($b->placename) && strtolower($b->placename) == strtolower($fuzzyname)) return 0;
            //equals fuzzyname
            if (strtolower($a->placename) == strtolower($fuzzyname) && strtolower($b->placename) != strtolower($fuzzyname)) return -1;
            if (strtolower($b->placename) == strtolower($fuzzyname) && strtolower($a->placename) != strtolower($fuzzyname)) return 1;
            //else startswith fuzzyname
            if (strpos(strtolower($a->placename), strtolower($fuzzyname)) === 0 && strpos(strtolower($b->placename), strtolower($fuzzyname)) !== 0) return -1;
            if (strpos(strtolower($b->placename), strtolower($fuzzyname)) === 0 && strpos(strtolower($a->placename), strtolower($fuzzyname)) !== 0) return 1;
            //else contains fuzzyname
            if (strpos(strtolower($a->placename), strtolower($fuzzyname)) !== false && strpos(strtolower($b->placename), strtolower($fuzzyname)) === false) return -1;
            if (strpos(strtolower($b->placename), strtolower($fuzzyname)) !== false && strpos(strtolower($a->placename), strtolower($fuzzyname)) === false) return 1;
            //else similar_text ranking to fuzzyname
            if ($isFuzzy) {
                $al = similar_text($fuzzyname, $a->placename);
                $bl = similar_text($fuzzyname, $b->placename);
                return $al === $bl ? 0 : ($al > $bl ? -1 : 1);
            }
            //We should never reach here?
            return 0;
        });
    }

    function diSubquery($qb, $parameters)
    {
        $subquery = '%' . $parameters['subquery'] . '%';
        $qb->where(function ($query) use ($subquery) {
            $query->where('lga', 'ILIKE', $subquery)
                ->orWhere('placename', 'ILIKE', $subquery)
                ->orWhere('description', 'ILIKE', $subquery)
                ->orWhere('latitude', 'ILIKE', $subquery)
                ->orWhere('longitude', 'ILIKE', $subquery)
                ->orWhere('feature_term', 'ILIKE', $subquery)
                ->orWhere('id', 'ILIKE', $subquery)
                ->orWhere('state', 'ILIKE', $subquery)
                ->orWhere('dataset_id', 'ILIKE', $subquery)
                ->orWhere('source', 'ILIKE', $subquery);
        });

        return $qb;
    }

    // Ivy's note: It seems that this is a redundant function (of diSubquery)?
    function gazSubquery($qb, $parameters)
    {
        $subquery = '%' . $parameters['subquery'] . '%';
        $qb->where(function ($query) use ($subquery) {
            $query->where('lga_name', 'ILIKE', $subquery)
                ->orWhere('placename', 'ILIKE', $subquery)
                ->orWhere('description', 'ILIKE', $subquery)
                ->orWhere('tlcm_latitude', 'ILIKE', $subquery)
                ->orWhere('tlcm_longitude', 'ILIKE', $subquery)
                ->orWhere('feature_term', 'ILIKE', $subquery)
                ->orWhere('parish', 'ILIKE', $subquery)
                ->orWhere('anps_id', 'ILIKE', $subquery)
                ->orWhere('state_code', 'ILIKE', $subquery)
                ->orWhere('original_data_source', 'ILIKE', $subquery);
        });

        return $qb;
    }

    /**
     * For all parameters we will be using, set them to null if they don't exists
     * Avoids the 'key does not exists' error when searching for an unset parameter
     *
     * Basically this lets us simply search $parameters['paramName'] without isset() because it is ugly - params will always now be set or null, never non-existant
     */
    function getParameters($parameters)
    {
        // to have a single id across databases etc, we are using the numeric auto incremented id, for uniqueness, human readability and no swearwords from alpha.
        // also it's convenient and the quickest to achieve right with urgency right now.
        // however, we want to avoid clashes across major data stores, so we namespace it with a single letter prefix, possibly more letters later.
        // so atm we will have a####### for anps id, and t####### for tlcmap ids. Case insensitive.
        // at the moment id= will just be a get query parameter, so the cannonical URL for a place will be like:
        // https://tlcmap.org/ghap/search?id=a301182
        // https://tlcmap.org/ghap/search?id=t1072
        // perhaps if we add Melbourne street data it might be https://tlcmap.org/ghap/search?id=m2872972
        // In future we can change this to also be more RESTful, eg: /ghap/ansp/301182
        // But will need to retain backward compatibility.
        // Henceforth retention of ID will be high priority.
        // Legacy is built on anps_id= and dataitemid= so we just map a### and T### to them respectivly

        // UPDATE:
        // Ok, finally figured out the Unique ID policy:
        // !!!!!!!!!!!! chosen solution for human freindly unique ids. Use namespace prefix 't' for user contribued, 'a' for anps
        // (this also allows for other major special purpose databases, eg Geoscience Australia, or Melb directories)
        // Use the autoincremented database id, to ensure uniqueness, guessability is not a security concern, but
        // convert it in code to base 16 (Hex) format. This format greatly reduces the amount of digits
        // (1 billion is still only 8char, 100million is 6char - so is within the order of magnitude we need to allow for,
        // and up to 6 or 8 is human friendly), and includes letters only up to f, in lower case, and includes on the vowel e and
        // not all consonants, so almost no risk of swear word (eg: with base 32 we would certainly get swearwords, eg record 522644 would be 'fuck').
        // Simply converting the actual DB id to hex, means we do not need to
        // seperately record and track UIDs involving DB calls to check uniqueness or find the last number to increment from,
        // leaving it to the system, which would introduces points of failure, confusion and human error.
        // We only need to convert to and from for display, update, retrieval with the one native function call.

        // The 'id' parameter actually means 'uid'.
        $parameters['id'] = (isset($parameters['id'])) ? $parameters['id'] : null;
        $parameters['paging'] = (isset($parameters['paging'])) ? $parameters['paging'] : null;
        $parameters['recordtype'] = (isset($parameters['recordtype'])) ? $parameters['recordtype'] : null;
        $parameters['searchlayers'] = (isset($parameters['searchlayers'])) ? $parameters['searchlayers'] : null;
        $parameters['lga'] = (isset($parameters['lga'])) ? $parameters['lga'] : null;
        $parameters['state'] = (isset($parameters['state'])) ? $parameters['state'] : null;
        $parameters['parish'] = (isset($parameters['parish'])) ? $parameters['parish'] : null;
        $parameters['anps_id'] = (isset($parameters['anps_id'])) ? $parameters['anps_id'] : null;
        $parameters['from'] = (isset($parameters['from'])) ? $parameters['from'] : null;
        $parameters['to'] = (isset($parameters['to'])) ? $parameters['to'] : null;
        $parameters['name'] = (isset($parameters['name'])) ? $parameters['name'] : null;
        $parameters['fuzzyname'] = (isset($parameters['fuzzyname'])) ? $parameters['fuzzyname'] : null;
        $parameters['containsname'] = (isset($parameters['containsname'])) ? $parameters['containsname'] : null;
        $parameters['format'] = (isset($parameters['format'])) ? $parameters['format'] : null;
        $parameters['searchdescription'] = (isset($parameters['searchdescription'])) ? $parameters['searchdescription'] : null;
        $parameters['download'] = (isset($parameters['download'])) ? $parameters['download'] : null;
        $parameters['bbox'] = (isset($parameters['bbox'])) ? $parameters['bbox'] : null;
        $parameters['polygon'] = (isset($parameters['polygon'])) ? $parameters['polygon'] : null;
        $parameters['circle'] = (isset($parameters['circle'])) ? $parameters['circle'] : null;
        $parameters['chunks'] = (isset($parameters['chunks'])) ? $parameters['chunks'] : null;
        $parameters['dataitemid'] = (isset($parameters['dataitemid'])) ? $parameters['dataitemid'] : null;
        $parameters['feature_term'] = (isset($parameters['feature_term'])) ? $parameters['feature_term'] : null;
        $parameters['extended_data'] = (isset($parameters['extended_data'])) ? $parameters['extended_data'] : null;
        $parameters['source'] = (isset($parameters['source'])) ? $parameters['source'] : null;
        $parameters['searchpublicdatasets'] = (isset($parameters['searchpublicdatasets'])) ? $parameters['searchpublicdatasets'] : null;
        $parameters['searchausgaz'] = (isset($parameters['searchausgaz'])) ? $parameters['searchausgaz'] : null;
        $parameters['sort'] = (isset($parameters['sort'])) ? $parameters['sort'] : null;
        $parameters['direction'] = (isset($parameters['direction'])) ? $parameters['direction'] : null;
        $parameters['subquery'] = (isset($parameters['subquery'])) ? $parameters['subquery'] : null;
        $parameters['datefrom'] = (isset($parameters['datefrom'])) ? $parameters['datefrom'] : null;
        $parameters['dateto'] = (isset($parameters['dateto'])) ? $parameters['dateto'] : null;
        $parameters['fuzzynames'] = (isset($parameters['fuzzynames'])) ? $parameters['fuzzynames'] : null;
        $parameters['names'] = (isset($parameters['names'])) ? $parameters['names'] : null;
        $parameters['containsnames'] = (isset($parameters['containsnames'])) ? $parameters['containsnames'] : null;

        // Check whether request is for data mapping
        $parameters['mapping'] = (isset($parameters['mapping'])) ? $parameters['mapping'] : null;
        // Check whether request is for mobility data mapping
        $parameters['mobility'] = (isset($parameters['mobility'])) ? $parameters['mobility'] : null;

        //Trove style parameters
        if (isset($parameters['exactq'])) $parameters['name'] = $parameters['exactq'];
        if (isset($parameters['q'])) $parameters['fuzzyname'] = $parameters['q'];
        if (isset($parameters['encoding'])) $parameters['format'] = $parameters['encoding'];
        if (isset($parameters['n'])) $parameters['paging'] = $parameters['n'];
        if (isset($parameters['l-lga'])) $parameters['lga'] = $parameters['l-lga'];
        if (isset($parameters['l-place'])) $parameters['state'] = $parameters['l-place'];
        if (isset($parameters['s'])) $parameters['from'] = $parameters['s'];
        if (isset($parameters['e'])) $parameters['to'] = $parameters['e'];

        //Google maps location bias - to replace bbox circle and polygon, and add search by exact coordinates
        $parameters['locationbias'] = (isset($parameters['locationbias'])) ? $parameters['locationbias'] : null;

        return $parameters;
    }

    /**
     * When the user tries to search for too many results on kml/json/csv OR too many per page for html,
     * We want to show the middleware warning message to inform the user that their results are being limited to the first n
     */
    function maxSizeCheck($results, $format, $MAX_PAGING)
    {
        if (!$format || $format == '' || $format == 'html') return false;

        if (!session()->has('paging.redirect.countchecked')) { //if we have more results than is allowed AND we have not run this block yet
            $resultscount = clone $results;
            $resultscount = count($resultscount); //get the count of results without getting results
            if ($resultscount > $MAX_PAGING) {
                session([
                    'paging.redirect.url' => url()->full(),
                    'paging.redirect.countchecked' => 'true'
                ]); //set the redirect url and set countchecked to true so we do not run this block twice
                return true;  //redirect to the max paging message page
            }
        }
        session()->forget('paging.redirect.countchecked'); //we have already run the above block for this query, forget this var for the next query
        return false;
    }

    protected $hasMobInfo = null;

    /**
     * Format and Redirect according to the return format type: kml, csv, json, html
     */
    function outputs($parameters, $results, $routeInResults = null)
    {
        /* Outputs */
        $headers = array(); //Create an array for headers
        $filename = "tlcmap_output"; //name for the output file

        $headers["hasmobinfo"] = $this->hasMobInfo;
        if ($parameters['format'] == "json") {
            // Note: not sure this chuck part gets called anywhere. May delete this after verification.
            /*
            Ivy's note:
                I guess that's for developing large dataset download and mapping functionality?
                Or maybe it's been deleted??
            */
            if ($parameters['chunks']) { //if we have sent the chunks parameter through
                return FileFormatter::jsonChunk($results, $parameters['chunks']); //download a zip containing the json files in chunks
            }
            $headers['Content-Type'] = 'application/json'; //set header content type
            if ($parameters['download']) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.json"'; //if we are downloading, add a 'download attachment' header
            if ($parameters['mapping'] && $parameters['mobility'] && $routeInResults) {
                return Response::make(FileFormatter::toGeoJSON($results, $parameters, $this->hasMobInfo, $routeInResults), '200', $headers);
            }
            return Response::make(FileFormatter::toGeoJSON($results, $parameters), '200', $headers); //serve the file to browser (or download)
        }
        if ($parameters['format'] == "csv") {
            $headers["Content-Type"] = "text/csv"; //set header content type
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.csv"'; //always download csv
            return FileFormatter::toCSV($results, $headers); //Download
        }
        if ($parameters['format'] == 'csvContent') {
            //Internal process only
            //Return the content of the csv report as string by stream_get_contents()
            //Used for ro-crate export of saved search results on multilayers
            return FileFormatter::toCSVContent($results, $this->hasMobInfo);
        }
        if ($parameters['format'] == "kml") {
            // Note: not sure this chuck part gets called anywhere. May delete this after verification.
            if ($parameters['chunks']) { //if we have sent the chunks parameter through
                return FileFormatter::kmlChunk($results, $parameters['chunks']); //download a zip contianing n chunks
            }
            $headers['Content-Type'] = 'text/xml'; //set header content type
            if ($parameters['download']) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.kml"'; //if we are downloading, add a 'download attachment' header
            return Response::make(FileFormatter::toKML2($results, $parameters), '200', $headers); //serve the file to browser (or download)
        }
        if ($parameters['format'] === 'rocrate') {
            $crate = ROCrateGenerator::generateSearchCrate($results, $parameters);
            if ($crate) {
                $timestamp = date("YmdHis");
                return response()->download($crate, "ghap-ro-crate-search-results-{$timestamp}.zip")->deleteFileAfterSend();
            }
        }
        // TO Check!!!
        if (array_key_exists('savedSearch', $parameters) && $parameters['savedSearch'] === true) {
            return ['details' => $results, 'hasmobinfo' => $this->hasMobInfo,];
        }

        $recordtypes = RecordType::types();
        //else, format as html
        return view('ws.ghap.places.show', [
            'details' => $results,
            'query' => $results,
            'recordtypes' => $recordtypes,
            'hasmobinfo' => $this->hasMobInfo,
        ]);
    }

    /**
     * Return an associative array with the bounding box parameters, if they exist
     * INPUT: "149.38879,-32.020594,149.454753,-31.946045"
     * OUTPUT: ["min_long" => 149.38879, "min_lat" => -32.020594, "max_long" => 149.454753, "max_lat" => -31.946045]
     */
    function getBbox(string $bbstr = null)
    {
        if ($bbstr) {
            // Use the commented-out solution if $bbstr is provided
            $values = explode(",", $bbstr);
            if (count($values) !== 4) return null; // Validate that there are exactly 4 values
            return [
                'min_long' => (float)$values[0],
                'min_lat' => (float)$values[1],
                'max_long' => (float)$values[2],
                'max_lat' => (float)$values[3]
            ];
        } else {
            // Fallback to the current solution if $bbstr is not provided
            $url = request()->fullUrl();
            $pattern = '/bbox=(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)/'; //regex to match bbox=12,13,14,15  can have negatives and 20 dec pl
            $bbox = preg_match($pattern, $url, $reg_out); //results are in indexes 1 3 5 and 7 of the $reg_out array

            if (sizeof($reg_out) != 8 && sizeof($reg_out) != 9) return null;  //8 if final float has no decimals, 9 if it does

            return [
                'min_long' => (float)$reg_out[1],
                'min_lat' => (float)$reg_out[3],
                'max_long' => (float)$reg_out[5],
                'max_lat' => (float)$reg_out[7]
            ];
        }
    }

    /**
     *    INPUT: "149.751587 -33.002617, 149.377796 -32.707289, 150.378237 -32.624051, 149.751587 -33.002617"
     *    OUTPUT: [149.751587,-33.002617,149.377796,-32.707289,150.378237,-32.624051,149.751587,-33.002617]
     */
    function getPolygon(string $polystr)
    { //returns an array of numbers representing lat/long, however each odd number is a space or a comma

        if ($polystr) {
            $values = explode(",", $polystr);
            $output = [];

            foreach ($values as $v) {
                $lnglat = explode(" ", trim($v));
                array_push($output, $lnglat[0]);
                array_push($output, $lnglat[1]);
            }

            return $output;
        } else {
            $url = request()->fullUrl();
            if (preg_match('/polygon=([^&]*)/', $url, $polymatch)) {
                $stripped = str_replace(['%20', '+'], '', $polymatch[1]); //strip spaces
                if (preg_match_all('/(-?\d{1,3}(\.\d{0,20})?)(%2C)?/', $stripped, $matches)) {
                    return $matches[1];
                }
            }
            return null;
        }
    }

    function getCircle(string $circlestr)
    {
        if (!$circlestr) return null;

        //Why are we matching on url when we have the exact values in circlestr??????
        $url = request()->fullUrl();
        $circle = preg_match('/circle=(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{0,20}(\.\d{0,20})?)/', $url, $reg_out);

        //$values = explode(",", $circlestr);

        if (sizeOf($reg_out) != 6 && sizeOf($reg_out) != 7) return null;  //8 if final float has no decimals, 9 if it does

        return ['long' => (float)$reg_out[1], 'lat' => (float)$reg_out[3], 'rad' => (float)$reg_out[5]];
        //return ['long' => (float)$values[0], 'lat' => (float)$values[1], 'rad' => (float)$values[2]];
    }

    /**
     * datefrom and dateto are the SEARCH PARAMETERS for date ranges
     * start and end is the date range of a specific data item in the DB that we are checking
     *
     * Given the date parameters and the date values for this row, determine if the row is within this date range (any overlap at all counts)
     * true if values are within parameters of the search, false if not.
     * any overlap is considered true
     *
     * we can assume at least one of datefrom or dateto is NOT null if this function is called, but
     */
    public static function dateSearch($datefrom, $dateto, $start, $end)
    {
        //if start AND end are both null, return false - This entry has no date values and should not be filtered in to a date search
        if (!$start && !$end) return false;

        //Grab the comparison values between parameters and values - will be null if a or b is null
        $start_from = GeneralFunctions::dateCompare($start, $datefrom); // 1 if start > datefrom, -1 if start < datefrom, 0 if start == datefrom, null if either a or b is null
        $end_from = GeneralFunctions::dateCompare($end, $datefrom); // 1 if end > datefrom, -1 if end < datefrom, 0 if end == datefrom, null if either a or b is null
        $start_to = GeneralFunctions::dateCompare($start, $dateto); // 1 if start > dateto, -1 if start < dateto, 0 if start == dateto, null if either a or b is null
        $end_to = GeneralFunctions::dateCompare($end, $dateto); // 1 if end > dateto, -1 if end < dateto, 0 if end == dateto, null if either a or b is null

        if (!$dateto) return ($start_from >= 0 || $end_from >= 0) ? true : false; //if no dateto search parameter, check item start >= datefrom parameter OR item end >= datefrom parameter

        if (!$datefrom) return ($start_to <= 0 || $end_to <= 0) ? true : false; //if no datefrom parameter, check start <= dateto OR end <= dateto

        //Assume both parameters exist from here

        if (!$end) return ($start_from >= 0 && $start_to <= 0) ? true : false; //if row has no end date value, check that the start date is between the datefrom and dateto parameters

        if (!$start) return ($end_from >= 0 && $end_to <= 0) ? true : false; //if row has no start date value, check that end date is between the datefrom and dateto parameters

        //Assume both parameters and both item date columns exist from here

        return ($start_to <= 0 && $end_from >= 0) ? true : false; //check there is some overlap between param and row date ranges - if start <= dateto AND end >= datefrom there must be overlap
    }

    /**
     * Paginate function that works for Collections - Converts Collection to LengthAwarePaginator
     * https://gist.github.com/ctf0/109d2945a9c1e7f7f7ea765a7c638db7
     */
    public static function paginate($items, $perPage = 15, $page = null)
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

    /**
     * Process and collect route information from the given results.
     *
     * @param Collection $results The collection of data items to process.
     * @param string $displayMode The display mode ('route', 'timeend', or 'timestart').
     * @return Collection A collection of processed route information.
     */
    public static function processRoutes(Collection $results, string $displayMode): Collection
    {
        $routeOrderColumn = $displayMode === 'route' ? 'stop_idx' : 'time_stop_idx';
        return $results
            ->filter(function ($item) {
                return !is_null($item->route_id);
            })
            ->groupBy('route_id')
            ->map(function ($group) use ($routeOrderColumn) {
                $sortedGroup = $group->sortBy($routeOrderColumn);
                $firstItem = $sortedGroup->first();

                if (!$firstItem || !$firstItem->route) {
                    // It shouldn't happen as that means route record is broken or current dataitem was cleared properly...
                    return null;
                }

                $datasetId = $firstItem->dataset_id;
                $routeId = $firstItem->route_id;
                $routeLayerText = "";
                $routeCoords = [];

                if ($sortedGroup->count() > 1) {
                    $routeCoords = $sortedGroup->map(function ($item) {
                        return [$item->longitude, $item->latitude];
                    })->filter()->values()->all();
                } else {
                    $onlyDataItem = $sortedGroup->first();
                    $routeCoords = [
                        [$onlyDataItem->longitude, $onlyDataItem->latitude],
                        [$onlyDataItem->longitude + 1, $onlyDataItem->latitude + 1]
                    ];
                    $routeLayerText = "Single Place From ";
                }

                // Set footer links.
                // Add original route id and layer name of this route in the footer link
                $routeLayerText .= "Route (ID) " . $routeId . " of TLCMap Layer " . $datasetId;
                $routeLayerUrl = url("publicdatasets/" . $datasetId);

                return [
                    'title' => $firstItem->route->title,
                    'route_id' => $routeId,
                    'route_title' => $firstItem->route->title,
                    'route_description' => $firstItem->route->description,
                    'route_size' => $firstItem->route->size,
                    'curr_size' => $sortedGroup->count(),
                    'routeCoords' => $routeCoords,
                    'routeLayerText' => $routeLayerText,
                    'routeLayerUrl' => $routeLayerUrl,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Add time_stop_idx to items with route_id and maintain original order.
     *
     * @param Collection $results The original collection of data items.
     * @param string $displayMode The display mode ('route', 'timeend', or 'timestart').
     * @return Collection The processed collection with added time_stop_idx.
     */
    public static function addTimeStopIndex(Collection $results, string $displayMode): Collection
    {
        $dateOrderColumn = $displayMode === 'timeend' ? 'dateend' : 'datestart';

        // consider to use lazy() when $withRoute is large
        [$withRoute, $withoutRoute] = $results->partition(function ($item) {
            return !is_null($item->route_id);
        });
        $processedWithRoute = $withRoute->groupBy('route_id')
            ->flatMap(function ($group) use ($dateOrderColumn) {
                return $group->sortBy($dateOrderColumn)
                    ->values()
                    ->map(function ($item, $index) {
                        $item->time_stop_idx = $index + 1;
                        return $item;
                    });
            });

        $updatedResults = $withoutRoute->concat($processedWithRoute);
        $originalOrder = $results->pluck('id')->flip();

        return $updatedResults->sortBy(function ($item) use ($originalOrder) {
            return $originalOrder[$item->id];
        });
    }

    /* File Formatting related helper functions all moved to App/Http/Helpers/FileFormatter.php */
}
