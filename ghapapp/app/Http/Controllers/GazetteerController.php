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
use Carbon\Carbon;

use TLCMap\Http\Helpers\GeneralFunctions;
 
class GazetteerController extends Controller
{
    /********************/
    /* PUBLIC FUNCTIONS */
    /********************/

    /**
     * Direct to a message informing the user that they have more results 
     *      OR results per page than the system can currently handle
     */
    public function maxPagingMessage(Request $request) {
        return view('ws.ghap.maxpagingmessage');
    }

    /**
     * This function stops an infinite loop between middleware and search
     * 
     * The search flow is index->middleware->search->middleware->search->searchresults, 
     *      as we need to kow the # of search results before we can tell the user if it is being limited
     */
    public function maxPagingRedirect(Request $request) {
        session(['paging.redirect.success' => 'true']);
        return redirect(session('paging.redirect.url'));
    }

    /**
     * Function for uploading a text file with a placename on each line
     * Results will be combined
     * 
     * Eg: Show all with placename Newcastle, Cessnock, Shoal Bay (23 results)
     */
    public function searchFromFile(Request $request) {
        $results = collect();  //Collection to hold results of search
        $bulkfile = $request->bulkfile; //Get file from request

        if(!empty($bulkfile)) {
		$ausgaz = DB::table('gazetteer.register'); //get anps Register table
		//and the tlcmap data
            $dataitems = Dataitem::select('title', DB::raw("'From Contributed Layer ' || dataset_id as flag"), 'id as dataitem_id', 'description', 'latitude as tlcm_latitude', 'longitude as tlcm_longitude', 
            'state as state_code', 'feature_term', 'source as original_data_source', 'lga as lga_name', 'external_url','datestart','dateend','created_at','updated_at', 'placename')
                ->whereHas('dataset', function ($q) { $q->where('public','=','true'); } );
            $bulkfile = $bulkfile->get();
            $delim = PHP_EOL;

            if( strpos($bulkfile, ',') !== false ) { //if file contains a comma, separate by comma
                $delim = ',';
            }

            $contents = explode($delim,$bulkfile); //turn into an array - explode separating by new lines
            foreach($contents as $line) {
                //$results->orWhere('placename','=',trim($line)); //OLD METHOD, DIRECT MATCHES - get the results matching this line from DB
		    //NEW METHOD, FUZZY
		    // ANPS Gaz:
                $ausgaz->orWhere(function ($query) use ($line) {
			$query->where('placename', 'ILIKE', '%'.trim($line).'%')->orWhereRaw('placename % ?', trim($line));
			//$query->where('placename', 'ILIKE', '%'.trim($line).'%')->orWhere('placename', 'SOUNDS LIKE', trim($line));
		});
		// User Layers, query both title and placename
                $dataitems->orWhere(function ($query) use ($line) {
                    $query->where('title', 'ILIKE', '%'.trim($line).'%')->orWhereRaw('title % ?', trim($line))->where('placename', 'ILIKE', '%'.trim($line).'%')->orWhereRaw('placename % ?', trim($line));
                });
            }           
            $dataitems = $dataitems->get();
            $results = $dataitems->merge($ausgaz->get()); //TODO merge not working???

            //Get results
            $paging = env('DEFAULT_PAGING', false);
            $results = $results->paginate($paging)->appends(request()->query()); 

            return view('ws.ghap.places.show', ['details' => $results, 'query' => $results]);
        }
        return redirect()->route('index'); //if the file was empty or not attached, go back to the main page
       
    }

    public function bulkFileParser(Request $request) {
        $bulkfile = $request->file->get();//get contents of file
        $names = str_replace(PHP_EOL, ',', $bulkfile); //replace all NEWLINES with commas

        //trim edges and replace extra spaces with a single space
        $names = explode(',', $names);
        foreach($names as &$name) {
            $name = trim($name); 
            $name = preg_replace('/\s+/', ' ', $name); //replace any instance of multiple spaces with a single space
        }

        return response()->json(['names' => join(",",$names)]); //join back into a single comma separated string
    }

    function kmlPolygonPlaceToPoints($place) {
        if (empty($place->Polygon)) return null;
        //"lon,lat,alt lon,lat,alt" OR "lon,lat,alt\nlon,lat,alt" allowed
        //$trimmed = trim(str_replace(" ", "", $place->Polygon->outerBoundaryIs->LinearRing->coordinates));
        $points = array_filter(preg_split('/[\s]+/', $place->Polygon->outerBoundaryIs->LinearRing->coordinates)); //split around space or newline //old:  //explode("\n",$trimmed);
        foreach($points as &$point) {
            $point = explode(",",$point);
        }
        return $points; //array of points where point is an array of long,lat,alt OR long,lat
    }

    function polygonPointsArrayToSQL($points) {
        //array of points where point is an array of long,lat,alt OR long,lat
        //POLYGON((lng1 lat1, lng2 lat2, lngn latn, lng1 lat1))
        $str = "POLYGON((";
        if (end($points) != reset($points)) array_push($points, reset($points)); //if final point does not equal first point, add first point as final point
        foreach($points as $point) {
            $str .= $point[0] . " " . $point[1] . ", ";
        }
        $str = substr($str,0,strlen($str)-2); //strip final comma
        $str .= "))";
        return $str;
    }

    public function searchFromKmlPolygon(Request $request) {
        $file = $request->polygonkml;
        if (empty($file)) return redirect()->route('index'); //if the file was empty or not attached, go back to the main page

        $results = DB::table('gazetteer.register'); //get Register table
       
        //Go through the kml and find Polygons
        $data = array();
        $xml_object = simplexml_load_file($file);
        $polygons = [];
    
        if (!empty($xml_object->Document->Folder)){
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
        for ($i=0; $i<count($polygons); $i++) {
            $results->OrWhereRaw("ST_CONTAINS(ST_GEOMFROMTEXT('" . $polygons[$i] . "'), ST_POINT(tlcm_longitude::double precision,tlcm_latitude::double precision) )");
        }

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
    public function search(Request $request, string $id=null) {
        $starttime = microtime(true);

        $that = $this; 

        /* DATA STRUCTURES */
        $asoc_array = array(); //Associative array to hold ANPS source data
        $results = collect();  //Collection to hold results of search

        /* ENV VARS */
        $MAX_PAGING = env('MAX_PAGING', false);     //should move the rest of the direct env calls to be config calls but this will take forever
        $DEFAULT_PAGING = env('DEFAULT_PAGING', false);

        /* PARAMETERS */
        $parameters = $this->getParameters($request->all());

        //app('log')->debug('Time after Parameter Get: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST

        if ($parameters['names']) $parameters['names'] = array_map('trim', explode(',', $parameters['names']));
        if ($parameters['fuzzynames']) $parameters['fuzzynames'] = array_map('trim', explode(',', $parameters['fuzzynames']));
        if ($parameters['containsnames']) $parameters['containsnames'] = array_map('trim', explode(',', $parameters['containsnames']));

        /* LIMIT PAGING */
        $paging = $parameters['paging'];
        if (!$paging && ($parameters['format']=='html' || $parameters['format']=='')) $paging = $DEFAULT_PAGING; //limit to DEFAULT if no limit set (to speed it up)
        if (!$paging || $paging > $MAX_PAGING) $paging = $MAX_PAGING; //limit to MAX if over max

        /* PUBLIC DATASET SEARCH */
        if ($parameters['searchpublicdatasets'] || $parameters['dataitemid']) {
            $publicdata = $this->searchPublicDatasets($parameters); //call our local function to handle public datasets
            if ($publicdata) $results = $results->merge($publicdata);
        }

        // app('log')->debug('Time after public dataset search: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST

        /* AUS GAZETTEER SEARCH */
        if ( !$parameters['dataitemid'] && ($parameters['searchausgaz'] || (!$parameters['searchpublicdatasets'] && !$parameters['searchausgaz'])) ) {
            //ausgaz is a QUERY BUILDER object as we havent called get() on it yet
            $ausgaz = $this->searchAusGaz($parameters, $id); //call our local function to handle Australian Gazetteer datasets

            // app('log')->debug('Time after ausgaz search: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST

            //Get the COLLECTION
            $ausgaz_collection = $ausgaz->get();

            // app('log')->debug('Time after ausgaz TO COLLECTION: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST

            //Modifying the collection directly, as datestart and dateend fields are TEXT fields not dates, simpler this way (might be a little slower)
            if ($parameters['datefrom'] || $parameters['dateto']) {
                $ausgaz_collection = $ausgaz_collection->filter(function ($v) use ($parameters, $that) { 
                    return $that::dateSearch($parameters['datefrom'], $parameters['dateto'], $v->tlcm_start, $v->tlcm_end); 
                });
            }

            // app('log')->debug('Time after ausgaz date filtering: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST

            //Merge the COLLECTION of ausgaz into the results COLLECTION
            $results = $results->merge($ausgaz_collection);

            /* MAX SIZE CHECK */
            if ($this->maxSizeCheck($results, $parameters['format'], $MAX_PAGING)) return redirect()->route('maxPagingMessage'); //if results > $MAX_PAGING show warning msg

            /* GET ANPS SOURCE DATA - TODO: CACHE THE JOIN SO WE DON'T HAVE TO PERFORM IT EACH TIME */
            //THIS IS MASSIVELY SLOW FOR LARGE DATASETS, DISABLING FOR NOW
            if ($parameters['format'] != 'csv') $asoc_array = $this->getANPSSourceData($ausgaz); //don't do it for csv, haven't decided on column format yet

            // app('log')->debug('Time after grabbing source data: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST
    
        }

        /* FUZZY NAME SORTING - skip if table sorting is applied */
        if ($parameters['fuzzyname'] && (!$parameters['sort'] || !$parameters['direction']))  $results = $this->fuzzysort($results,$parameters['fuzzyname'],true); //last param true if fuzzy, false if contains
        else if ($parameters['containsname'] && (!$parameters['sort'] || !$parameters['direction']))  $results = $this->fuzzysort($results,$parameters['containsname'],false);

        /* SORT AND DIRECTION */
        if ($parameters['sort'] && $parameters['direction']) $results = ($parameters['direction'] == 'asc') ? $results->sortBy($parameters['sort']) : $results->sortByDesc($parameters['sort']);

        /* APPLY PAGING/LIMITING */
        $results = $this->paginate($results, $paging); //Turn into a LengthAwarePaginator - APPLIES LIMITING!
        
       // app('log')->debug('Time before output function: ' . (microtime(true) - $starttime)); //DEBUG LOGGING TEST

        /* OUTPUT */
        return $this->outputs($parameters, $results, $asoc_array);
    }

  

    /********************/
    /* SEARCH FUNCTIONS */
    /********************/

    function searchPublicDatasets($parameters) {
        $that = $this; //self reference to use the functions in this controller

        /* SKIP IF ANPS ID WAS SEARCHED */
        if ($parameters['anps_id']) return null;

	/* Get dataitems where their parent dataset is public */

	// Bill Pascoe: This is tricky. Moving to postgres the 'From Public Dataset' bit is causing problems. The intention appears to be to add a column with texts that says something like 
	// 'From Public Dataset X' and call the column 'flag'. This isn't the text that ultimately gets displayed either. It seems like a strange and dodgy way of going about things
	// but haven't followed the workflow through to see why. Will just change this a bit to get it working for now.
	// Previous code is commented out below. It looks like the '+' sign is what pgres sees as an invalid operator, so is maybe a MySQL specific way to concat strings.
	// So just removing + dataset_id for now. Maybe redundant old untidy code.
	//
	// In trying to figure out why this is creating a string to put in a column that says 'From Public Dataset X' and call that column 'flag' it seems it was used to create
	// the link from this item in search results to the public dataset. However, that bit of code in table.blade was adding the 'flag' value to the end of a URL, which would tack this whole
	// string on the end, which didn't work anyway, as the link to the dataset really just needs the dataset id tacked on the end. So I'm still not sure what this is for,
	// except perhaps a fudge to the the right human readable text in to GeoJSON and KML output. Maybe check that out. In the mean time, just correcting table.blade.php to 
	// use dataset_id instead of 'flag'.
        $dataitems = Dataitem::select('title', 'dataset_id', 'recordtype_id', DB::raw("'From Contributed Layer ' || dataset_id as flag"), 
        'id as dataitem_id', 'description', 'latitude as tlcm_latitude', 'longitude as tlcm_longitude', 
            'state as state_code', 'feature_term', 'source as original_data_source', 'lga as lga_name', 
            'external_url','datestart as tlcm_start','dateend as tlcm_end','extended_data','created_at','updated_at','placename')
                ->with(['dataset' => function ($q) { $q->select('id', 'name','warning'); }])
                ->whereHas('dataset', function ($q) { $q->where('public','=','true'); } );

//        $dataitems = Dataitem::select('placename', 'dataset_id', DB::raw("'From Public Dataset ' + dataset_id as flag"), 'id as dataitem_id', 'description', 'latitude as TLCM_Latitude', 'longitude as TLCM_Longitude',
//	    'state as state_code', 'feature_term', 'source as ORIGINAL_DATA_SOURCE', 'lga as lga_name', 'parish', 'external_url','datestart as TLCM_start','dateend as TLCM_end','created_at','updated_at')
//	        ->with(['dataset' => function ($q) { $q->select('id', 'name'); }])
//	        ->whereHas('dataset', function ($q) { $q->where('public','=','1'); } );


        /* GET BBOX PARAMS */
        $bbox = ($parameters['bbox']) ? $this->getBbox($parameters['bbox']) : null;
        $polygon = ($parameters['polygon']) ? $this->getPolygon($parameters['polygon']) : null;
        $circle = ($parameters['circle']) ? $this->getCircle($parameters['circle']) : null;

        //BULK SEARCH FROM FILE - set names to be either names fuzzynames containsnames or null - filter out empty strings
        $names;
        switch(!null) {
            case ($parameters['names']): $names = array_filter(array_map('trim', $parameters['names'])); break; 
            case ($parameters['fuzzynames']): $names = array_filter(array_map('trim', $parameters['fuzzynames'])); break; 
            case ($parameters['containsnames']): $names = array_filter(array_map('trim', $parameters['containsnames'])); break; 
            default: $names = null;
        }

        if ($names) { //if we are bulk searching from file
            if (!empty($names)) { //if we dont have an empty array
                //If we are bulk searching from file, skip name and fuzzyname search and search from file instead
                if ($parameters['names']) {
                    $dataitems->where(function ($query) use ($names) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('title','=',$firstcase)->orWhere('placename','=',$firstcase);
                        foreach($names as $line) {
                            $query->orWhere('title','=',$line)->orWhere('placename','=',$firstcase);
                        }
                    });
                }
                else if ($parameters['fuzzynames']) {
                    $dataitems->where(function ($query) use ($names) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
			$query->where('title', 'ILIKE', '%'.$firstcase.'%')->orWhereRaw('placename % ?', $firstcase);
			//$query->where('placename', 'ILIKE', '%'.$firstcase.'%')->orWhere('placename', 'SOUNDS LIKE', $firstcase);
                        foreach($names as $line) {
                            $query->orWhere('title', 'ILIKE', '%'.$line.'%')->orWhereRaw('placename % ?', $line);
                        } 
                    });
                }
                else if ($parameters['containsnames']) {
                    $dataitems->where(function ($query) use ($names) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('title', 'ILIKE', '%'.$firstcase.'%')->orWhere('placename', 'ILIKE', '%'.$firstcase.'%');
                        foreach($names as $line) {
                            $query->orWhere('title', 'ILIKE', '%'.trim($line).'%')->orWhere('placename', 'ILIKE', '%'.trim($line).'%');
                        } 
                    });
                }
            }
            else $dataitems->where('title', '=', null); //we did a bulk search but all of the names equated to empty strings! Show no results
        }
        else {
            if ($parameters['name'])  $dataitems->where('title', '=', $parameters['name']);
            else if ($parameters['fuzzyname'])  { 
                $dataitems->where(function ($query) use ($parameters) {
			$query->where('title', 'ILIKE', '%'.$parameters['fuzzyname'].'%')->orWhereRaw('title % ?', $parameters['fuzzyname'])
				->orWhere('placename', 'ILIKE', '%'.$parameters['fuzzyname'].'%')->orWhereRaw('placename % ?', $parameters['fuzzyname']);
			//$query->where('placename', 'ILIKE', '%'.$parameters['fuzzyname'].'%')->orWhere('placename', 'SOUNDS LIKE', $parameters['fuzzyname']);
                });     
            }
            else if ($parameters['containsname']) {
                $dataitems->where('title', 'ILIKE', '%'.$parameters['containsname'].'%')->orWhere('placename', 'ILIKE', '%'.$parameters['containsname'].'%');    
            }
        }

        /* BUILD SEARCH QUERY WITH PARAMS */
        if ($parameters['lga'])  $dataitems->where('lga', '=', $parameters['lga']);
        if ($parameters['dataitemid'])  $dataitems->where('id', '=', $parameters['dataitemid']);
        if ($parameters['from'])  $dataitems->where('id', '>=', $parameters['from']);
        if ($parameters['to'])  $dataitems->where('id', '<=', $parameters['to']);
        if ($parameters['state'])  $dataitems->where('state', '=', $parameters['state']);
        if ($parameters['feature_term'])  $dataitems->where('feature_term', '=', $parameters['feature_term']);

        /* Check for date overlap (a.start < b.end && a.end > b.start) */
            //NOTE: we are NOT currently giving special consideration to cases where a dataitem has 1 but not both start and end dates, TODO: Accomodate this odd situation
        //If user specifies both a from AND to date they want all instances where dataitems were active in this period (inclusive)
        // if ($parameters['dateto'] && $parameters['datefrom']) {
        //     $dataitems->where( function($q) use ($parameters) { 
        //         $q->whereDate('datestart', '<=', $parameters['dateto']); //$q->whereRaw( (Carbon::parse('datestart')->lessThanOrEqualTo(Carbon::parse($parameters['dateto']))) . ' = true'  );
        //         $q->whereDate('dateend', '>=',  $parameters['datefrom']); //$q->whereRaw( (Carbon::parse('datestart')->lessThanOrEqualTo(Carbon::parse($parameters['dateto']))) . ' = true'  ); 
        //     });
        // }
        // //if user ONLY specifies an end date, they want all instances where dataitems were active up to and including this date 
        // else if ($parameters['dateto']) $dataitems->whereDate('datestart', '<=', $parameters['dateto']); //$dataitems->whereRaw( (Carbon::parse('datestart')->lessThanOrEqualTo(Carbon::parse($parameters['dateto']))) . ' = true'  ); 
        // //if user ONLY specifies a start date, they want all instances where dataitems were active on this date or any date afterward
        // else if ($parameters['datefrom']) $dataitems->whereDate('dateend', '>=', $parameters['datefrom']); //$dataitems->whereRaw( (Carbon::parse('datestart')->lessThanOrEqualTo(Carbon::parse($parameters['dateto']))) . ' = true'  );
    
        if ($bbox) {
            $dataitems->where('latitude', '>=', $bbox['min_lat']);
            $dataitems->where('latitude', '<=', $bbox['max_lat']);

            if ($bbox['min_long'] <= $bbox['max_long']) { //if min is lower than max we have not crossed the 180th meridian
                $dataitems->where('longitude', '>=', $bbox['min_long']);
                $dataitems->where('longitude', '<=', $bbox['max_long']);
            }
            else { //else we have crossed the 180th meridian
                $dataitems->where('longitude', '>=', $bbox['min_long'])->orWhere('longitude', '<=', $bbox['max_long']); //TODO: does this need a where(function) encapsulation?
            }
        }
        if ($polygon) { //sql: WHERE ST_CONTAINS(ST_GEOMFROMTEXT('POLYGON((lng1 lat1, lng2 lat2, lngn latn, lng1 lat1))'), POINT(longitude,latitude) )
            $polygonsql = "ST_GEOMFROMTEXT('POLYGON((";
            for ($i=0; $i < count($polygon); $i+=2) { //for each point
                $polygonsql .= $polygon[$i] . " "; //long
                $polygonsql .= $polygon[$i+1] . ", "; //lat
            }
            $polygonsql = substr($polygonsql,0,strlen($polygonsql)-2); //strip final comma
            $polygonsql .= "))')";
            $dataitems->whereRaw("ST_CONTAINS(" . $polygonsql . ", ST_POINT(longitude,latitude) )")->orWhereRaw("ST_CONTAINS(" . $polygonsql . ", ST_POINT(longitude+360,latitude) )")
                ->orWhereRaw("ST_CONTAINS(" . $polygonsql . ", ST_POINT(longitude-360,latitude) )"); //TODO: does this need a where(function) encapsulation?
	    // $dataitems->whereRaw("ST_CONTAINS(" . $polygonsql . ", POINT(longitude,latitude) )")->orWhereRaw("ST_CONTAINS(" . $polygonsql . ", POINT(longitude+360,latitude) )")
	    //                 ->orWhereRaw("ST_CONTAINS(" . $polygonsql . ", POINT(longitude-360,latitude) )"); //TODO: does this need a where(function) encapsulation?	
	}
        if ($circle) {
            $dataitems->whereRaw("ST_DISTANCE( ST_POINT(" . $circle['long'] . "," . $circle['lat'] . "), ST_POINT(longitude,latitude) ) <= " . $circle['rad']); //NOTE: ST_DISTANCE for mariaDB, ST_DISTANCE_SPHERE for mysql
     // $dataitems->whereRaw("ST_DISTANCE( POINT(" . $circle['long'] . "," . $circle['lat'] . "), POINT(longitude,latitude) ) <= " . $circle['rad']); //NOTE: ST_DISTANCE for mariaDB, ST_DISTANCE_SPHERE for mysql 
	}
        if ($circle) {
            //NOTE: in mysql use  whereRaw( "ST_DISTANCE_SPHERE( POINT(" . $circle['long'] . "," . $circle['lat'] . "), POINT(TLCM_Longitude,TLCM_Latitude) ) <= " . $circle['rad'] );
            //for mariadb: http://sqlfiddle.com/#!2/abcc8/4/0
            $that = $this; //access private class functions from inside functions
            $dataitems->where(function ($query) use ($circle, $that) {
                $query->whereRaw($that->circleWrapString($circle, "latitude", "longitude"))->OrWhereRaw($that->circleWrapString($circle, "latitude", "longitude+360"))
                    ->orWhereRaw($that->circleWrapString($circle, "latitude", "longitude-360"));
            });
        }
        if ($parameters['subquery']) {
            $dataitems = $this->diSubquery($dataitems, $parameters);
        }

        $collection = $dataitems->whereHas('dataset', function ($q) { $q->where('public','=','true'); } )->get(); //needs to be applied a second time for some reason (maybe because of the subquery?)

        //Modifying the collection directly, as datestart and dateend fields are TEXT fields not dates, simpler this way (might be a little slower)
        if ($parameters['dateto'] || $parameters['datefrom']) {
            $collection = $collection->filter(function ($v) use ($parameters, $that) { return $that::dateSearch($parameters['datefrom'], $parameters['dateto'], $v->tlcm_start, $v->tlcm_end); });
        }

        return $collection; 
    }
    
    function searchAusGaz($parameters, $id) {
        /* Get Tables */
        $results = DB::table('gazetteer.register'); //Contains geographical places in the Gazetteer

        //No longer needed? Works without this
        /* ANPS GAZ ENTRIES HAVE NO DATES! RETURN ZERO RESULTS IF DATE FIELDS ARE REQUESTED */
        // if ($parameters['datefrom'] || $parameters['dateto']) {
        //     $results->where('anps_id', '=', '-99999999');
        //     return $results;
        // }

        /* GET BBOX PARAMS */
        $bbox = ($parameters['bbox']) ? $this->getBbox($parameters['bbox']) : null;
        $polygon = ($parameters['polygon']) ? $this->getPolygon($parameters['polygon']) : null;
        $circle = ($parameters['circle']) ? $this->getCircle($parameters['circle']) : null;

        //BULK SEARCH FROM FILE - set names to be either names fuzzynames containsnames or null - filter out empty strings
        $names;
        switch(!null) {
            case $parameters['names']: $names = array_filter(array_map('trim', $parameters['names'])); break; 
            case $parameters['fuzzynames']: $names = array_filter(array_map('trim', $parameters['fuzzynames'])); break; 
            case $parameters['containsnames']: $names = array_filter(array_map('trim', $parameters['containsnames'])); break; 
            default: $names = null;
        }

        if ($names) { //if we are bulk searching from file
            if (!empty($names)) { //if we dont have an empty array
                if ($parameters['names']) {
                    $results->where(function ($query) use ($names) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('placename','ILIKE',$firstcase);
                        foreach($names as $line) {
                            $query->orWhere('placename','=',$line);
                        } 
                    });
                }
                else if ($parameters['fuzzynames']) {
                    $results->where(function ($query) use ($names) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
			$query->where('placename', 'ILIKE', '%'.$firstcase.'%')->orWhereRaw('placename % ?', $firstcase);
			//  $query->where('placename', 'ILIKE', '%'.$firstcase.'%')->orWhere('placename', 'SOUNDS LIKE', $firstcase);
                        foreach($names as $line) {
				$query->orWhere('placename', 'ILIKE', '%'.$line.'%')->orWhereRaw('placename % ?', $line);
				//$query->orWhere('placename', 'ILIKE', '%'.$line.'%')->orWhere('placename', 'SOUNDS LIKE', $line);
                        } 
                    });
                }
                else if ($parameters['containsnames']) {
                    $results->where(function ($query) use ($names) {
                        $firstcase = array_shift($names); //have to do a where() with firstcase first or the orWhere() fails
                        $query->where('placename', 'ILIKE', '%'.$firstcase.'%');
                        foreach($names as $line) {
                            $query->orWhere('placename', 'ILIKE', '%'.$line.'%');
                        } 
                    });
                }
            }
            else $results->where('placename', '=', null); //we did a bulk search but all of the names equated to empty strings! Show no results
        }
        else { //we are not bulk searching from file, we are searching for a single placename
            if ($parameters['name'])  $results->where('placename', 'ILIKE', $parameters['name']); //exact search
            else if ($parameters['fuzzyname']) {
		    $results->where(function ($query) use ($parameters) { //fuzzy search
			// in postgres there are advanced fuzzy matching techniques. but you have to enable them, eg by enabling pg_trgm
			// which lets you use the % operator for a quick fuzzy match. There are others worth exploring later.
			    // However eloquent doesn't let you use the % as an operator, and if you try to use DB::raw '%' for the operator
			    // it assumes you only have a two parameter method, and treat it like 'Where placename = %' instead of 'placemane % myfuzzyname
			    // So we have to use orWhereRaw to write the whole where clause ourselves, but that is vulnerable to SQL Injection
			    // so we use the ? placeholder, followed by the input parameter, which is designed to aviod SQL injection.
			    // NOTE that the % approach gives quite different results to MySQL 'SOUNDS LIKE'. Though they are both fuzzy.
			    // No time at present to investigate the optimum or best.
			    // but btw, when Ben McDonnell was looking into Levenstein distance, it took minutes. But I think he had to DIY when using MySQL
			    // whereas PG has a Lewenshtien built in, so could work. Would need to rework this fuzzy match including the  
			    // fuzzy sorting function in this code below, which would then just use the rank returned by the query.
			$query->where('placename', 'ILIKE', '%'.$parameters['fuzzyname'].'%')->orWhereRaw('placename % ?', $parameters['fuzzyname'] );
		//	$query->where('placename', 'ILIKE', '%'.$parameters['fuzzyname'].'%')->orWhere('placename', 'SOUNDS LIKE', $parameters['fuzzyname']);
                });
            }  
            else if ($parameters['containsname']) {
                $results->where('placename', 'ILIKE', '%'.$parameters['containsname'].'%');    
            }
        }

        /* GET ID FROM URL */
        if ($id) $results->where('anps_id', '=', $id);

        /* BUILD SEARCH QUERY WITH PARAMS */
        if ($parameters['anps_id'])  $results->where('anps_id', '=', $parameters['anps_id']);
        if ($parameters['from'])  $results->where('anps_id', '>=', $parameters['from']);
        if ($parameters['to'])  $results->where('anps_id', '<=', $parameters['to']);
        if ($parameters['lga'])  $results->where('lga_name', '=', $parameters['lga']);
        if ($parameters['state'])  $results->where('state_code', '=', $parameters['state']);
        if ($parameters['source'])  $results->where('original_data_source', '=', $parameters['source']);
        if ($parameters['feature_term'])  $results->where('feature_term', '=', $parameters['feature_term']);
        if ($parameters['parish'])  $results->where('parish', '=', $parameters['parish']);
        if ($parameters['datefrom'] || $parameters['dateto']) $results->where(function ($q) {
            $q->where('tlcm_start', '!=', null)->orWhere('tlcm_end', '!=', null);
        });
        if ($bbox) { //assume values are in range -180 to 180, as we have dealt with that in leaflet
            $results->whereRaw("tlcm_latitude::double precision >= ?", $bbox['min_lat']);
            $results->whereRaw("tlcm_latitude::double precision <= ?", $bbox['max_lat']);
           // $results->where("tlcm_latitude::double precision", '>=', $bbox['min_lat']);
           // $results->where("tlcm_latitude::double precision", '<=', $bbox['max_lat']);

            if ($bbox['min_long'] <= $bbox['max_long']) { //if min is lower than max we have not crossed the 180th meridian
                $results->whereRaw("tlcm_longitude::double precision >= ? ", $bbox['min_long']);
                $results->whereRaw("tlcm_longitude::double precision <= ?", $bbox['max_long']);
            }
            else { //else we have crossed the 180th meridian
                $results->whereRaw("tlcm_longitude::double precision >= ?", $bbox['min_long'])->orWhereRaw("tlcm_longitude::double precision <= ?", $bbox['max_long']); //TODO: does this need a where(function) encapsulation?
            }
        }
        if ($polygon) {
            $polygonsql = "ST_GEOMFROMTEXT('POLYGON((";
            for ($i=0; $i < count($polygon); $i+=2) { //for each point
                if (!is_numeric($polygon[$i])) { throw new Exception('Invalid polygon value. Must be number only.');} // validate because it's raw SQL
                $polygonsql .= $polygon[$i] . " "; //long
                $polygonsql .= $polygon[$i+1] . ", "; //lat
            }
            $polygonsql = substr($polygonsql,0,strlen($polygonsql)-2);
            $polygonsql .= "))')";
            $results->whereRaw("ST_CONTAINS( " . $polygonsql . ", ST_POINT(tlcm_longitude::double precision,tlcm_latitude::double precision) )" )
            ->orWhereRaw("ST_CONTAINS( " . $polygonsql . " , ST_POINT(tlcm_longitude::double precision +360,tlcm_latitude::double precision) )")
            ->orWhereRaw("ST_CONTAINS( " . $polygonsql . " , ST_POINT(tlcm_longitude::double precision -360,tlcm_latitude::double precision) )"); //TODO: does this need a where(function) encapsulation?
        }
        if ($circle) {
            //NOTE: in mysql use  whereRaw( "ST_DISTANCE_SPHERE( POINT(" . $circle['long'] . "," . $circle['lat'] . "), POINT(TLCM_Longitude,TLCM_Latitude) ) <= " . $circle['rad'] );
		//for mariadb: http://sqlfiddle.com/#!2/abcc8/4/0
		//
		//BIll Pascoe: note, from postgres migration - it may be that postgis can handle this with a much simpler command, but we can leave that till another time, if this works. Time is short.
            $that = $this; //access $this inside the next function by setting it as $that
            $results->where(function ($query) use ($circle, $that) {
                $query->whereRaw($this->circleWrapString($circle, "tlcm_latitude::double precision", "tlcm_longitude::double precision"))->OrWhereRaw($this->circleWrapString($circle, "tlcm_latitude::double precision", "tlcm_longitude::double precision +360"))
                ->orWhereRaw($this->circleWrapString($circle, "tlcm_latitude::double precision", "tlcm_longitude::double precision -360")); //TODO: does this need a where(function) encapsulation?
            });
        }
        if ($parameters['subquery']) {
            $results = $this->gazSubquery($results, $parameters);
        }

        return $results;
    }
    
    /********************/
    /* HELPER FUNCTIONS */
    /********************/
    function circleWrapString($circle, $latCol, $long) {
        return "111.1111 " .
        "* DEGREES(ACOS(COS(RADIANS(" . $circle['lat'] . ")) " . 
        "* COS(RADIANS(" . $latCol . ")) " .
        "* COS(RADIANS(" . $circle['long'] . ") - RADIANS(" . $long . ")) " .
        "+ SIN(RADIANS(" . $circle['lat'] . ")) " .
        "* SIN(RADIANS(" . $latCol . ")))) " . 
        "<= " . $circle['rad']/1000;
    }

    function fuzzysort($collection,$fuzzyname, $isFuzzy) {
        return $collection->sort(function($a,$b) use ($fuzzyname, $isFuzzy) {
            //if two entries are exactly identical, the order does not matter
            if (strtolower($a->placename) == strtolower($b->placename) && strtolower($b->placename) == strtolower($fuzzyname)) return 0;
            //equals fuzzyname
            if (strtolower($a->placename) == strtolower($fuzzyname) && strtolower($b->placename) != strtolower($fuzzyname) ) return -1;
            if (strtolower($b->placename) == strtolower($fuzzyname) && strtolower($a->placename) != strtolower($fuzzyname) ) return 1;
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

    function diSubquery($qb, $parameters) {
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

    function gazSubquery($qb, $parameters) {
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
    function getParameters($parameters) {
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
        if (isset($parameters['id'])) {
            
            if (stripos($parameters['id'], 't') !== false) {
                $hid = preg_replace('/^t/', '', $parameters['id']);
                $parameters['dataitemid'] = base_convert($hid, 16, 10);
            } else if (stripos($parameters['id'], 'a') !== false) {
                $hid = preg_replace('/^a/', '', $parameters['id']);
                $parameters['anps_id'] = base_convert($hid, 16, 10);
            }
        }

        $parameters['paging'] = (isset($parameters['paging'])) ? $parameters['paging'] : null;
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
        $parameters['download'] = (isset($parameters['download'])) ? $parameters['download'] : null;
        $parameters['bbox'] = (isset($parameters['bbox'])) ? $parameters['bbox'] : null;
        $parameters['polygon'] = (isset($parameters['polygon'])) ? $parameters['polygon'] : null;
        $parameters['circle'] = (isset($parameters['circle'])) ? $parameters['circle'] : null;
        $parameters['chunks'] = (isset($parameters['chunks'])) ? $parameters['chunks'] : null;
        $parameters['dataitemid'] = (isset($parameters['dataitemid'])) ? $parameters['dataitemid'] : null;
        $parameters['feature_term'] = (isset($parameters['feature_term'])) ? $parameters['feature_term'] : null;
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
     * Get the ANPS source data information for these register entries and return it in an associative array
     */
    function getANPSSourceData($results) {
        $asoc_array = array();
        $doc = DB::table('gazetteer.documentation'); //Links places to sources
        $src = DB::table('gazetteer.source'); //Sources used within the gazetteer data collection

        //in mysql: SELECT DISTINCT documentation.anps_id, source.* FROM source INNER JOIN documentation ON source.source_id = documentation.doc_source_id
        $source_anps_matches = $src->join('gazetteer.documentation','source.source_id','=','documentation.doc_source_id')
            ->select('documentation.anps_id', 'source.*')->distinct()->orderBy('anps_id')->get();
//       var_dump($source_anps_matches); 
        //for each anps_id we have in results,
        $results->orderBy('anps_id')->chunk(50000, function($results_chunk) use ($source_anps_matches, &$asoc_array){
		foreach ($results_chunk as $result) {
			//var_dump($result);exit;
		    if ($result->original_data_source != 'ANPS Research') continue; //Skip ones that do not have ANPS Research tagged on them - they dont have matches anyways
                $curr_srcs = []; //reset

                //Get the sources for that id from the match query
                foreach($source_anps_matches as $match_row) {
                    if ($match_row->anps_id > $result->anps_id) break;
                    if ($match_row->anps_id == $result->anps_id) array_push($curr_srcs, $match_row);
                }

                $asoc_array[$result->anps_id] = $curr_srcs;
            }
        });

        return $asoc_array;
    }
    
    /**
     * When the user tries to search for too many results on kml/json/csv OR too many per page for html, 
     * We want to show the middleware warning message to inform the user that their results are being limited to the first n
     */
    function maxSizeCheck($results, $format, $MAX_PAGING) {
        if (!$format || $format == '' || $format == 'html') return false;

        if (!session()->has('paging.redirect.countchecked')) { //if we have more results than is allowed AND we have not run this block yet
            $resultscount = clone $results;
            $resultscount = count($resultscount); //get the count of results without getting results
            if ($resultscount > $MAX_PAGING) {
                session(['paging.redirect.url' => url()->full(), 'paging.redirect.countchecked' => 'true']); //set the redirect url and set countchecked to true so we do not run this block twice
                return true;  //redirect to the max paging message page
            }
        }
        session()->forget('paging.redirect.countchecked'); //we have already run the above block for this query, forget this var for the next query
        return false;
    }
    
    /**
     * Format and Redirect according to the return format type: kml, csv, json, html
     */
    function outputs($parameters, $results, $asoc_array) {
        /* Outputs */
        $headers = array(); //Create an array for headers
        $filename = "tlcmap_output"; //name for the output file

        if ($parameters['format'] == "json") {
            if ($parameters['chunks']) { //if we have sent the chunks parameter through
                return FileFormatter::jsonChunk($results,$parameters['chunks'],$asoc_array); //download a zip containing the json files in chunks
            }
           // Log::error("asdfasdf 011");
            //if (!empty($results_collection)) $results = $results->getCollection(); //if fuzzyname, collect for json
            $headers['Content-Type'] = 'application/json'; //set header content type
            if($parameters['download']) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.json"'; //if we are downloading, add a 'download attachment' header
            return Response::make(FileFormatter::toGeoJSON($results,$asoc_array), '200', $headers); //serve the file to browser (or download)
        }
        if ($parameters['format'] == "csv") { 
           // Log::error("asdf 000");
            $headers["Content-Type"] = "text/csv"; //set header content type
            $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.csv"'; //always download csv
            return FileFormatter::toCSV($results,$headers); //Download
        }
        if ($parameters['format'] == "kml") {
            if ($parameters['chunks']) { //if we have sent the chunks parameter through
                return FileFormatter::kmlChunk($results,$parameters['chunks'],$asoc_array); //download a zip contianing n chunks
            }
            $headers['Content-Type'] = 'text/xml'; //set header content type
            if($parameters['download']) $headers['Content-Disposition'] = 'attachment; filename="' . $filename . '.kml"'; //if we are downloading, add a 'download attachment' header
            return Response::make(FileFormatter::toKML2($results,$asoc_array,$parameters), '200', $headers); //serve the file to browser (or download)
        }

        //else, format as html
        return view('ws.ghap.places.show', ['details' => $results, 'query' => $results, 'sources' => $asoc_array]);
    }

    /**
     * Return an associative array with the bounding box parameters, if they exist
     * INPUT: "149.38879,-32.020594,149.454753,-31.946045"
     * OUTPUT: ["min_long" => 149.38879, "min_lat" => -32.020594, "max_long" => 149.454753, "max_lat" => -31.946045]
     */
    function getBbox(string $bbstr) {
        if (!$bbstr) return null;

        // $values = explode(",", $bbstr);
        // return ['min_long' => (float)$values[0], 'min_lat' => (float)$values[1], 'max_long' => (float)$values[2], 'max_lat' => (float)$values[3]];

        $url = request()->fullUrl();
        $pattern = '/bbox=(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)/'; //regex to match bbox=12,13,14,15  can have negatives and 20 dec pl
        $bbox = preg_match($pattern,$url,$reg_out); //results are in indexes 1 3 5 and 7 of the $reg_out array
            
        if(sizeOf($reg_out) != 8 && sizeOf($reg_out) != 9) return null;  //8 if final float has no decimals, 9 if it does

        return ['min_long' => (float)$reg_out[1], 'min_lat' => (float)$reg_out[3], 'max_long' => (float)$reg_out[5], 'max_lat' => (float)$reg_out[7]];

    }

    /**
     *    INPUT: "149.751587 -33.002617, 149.377796 -32.707289, 150.378237 -32.624051, 149.751587 -33.002617"
     *    OUTPUT: [149.751587,-33.002617,149.377796,-32.707289,150.378237,-32.624051,149.751587,-33.002617]
    */
    function getPolygon(string $polystr) { //returns an array of numbers representing lat/long, however each odd number is a space or a comma
        
        if (!$polystr) return null;

        // $values = explode(",", $polystr);
        // $output = [];

        // foreach($values as $v) {
        //     $lnglat = explode(" ", trim($v));
        //     array_push($output, $lnglat[0]);
        //     array_push($output, $lnglat[1]);
        // }

        // return $output;

        $url = request()->fullUrl();
        if (preg_match('/polygon=([^&]*)/', $url, $polymatch)) {
            $stripped = str_replace(['%20', '+'], '', $polymatch[1]); //strip spaces
            if (preg_match_all('/(-?\d{1,3}(\.\d{0,20})?)(%2C)?/', $stripped, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    function getCircle(string $circlestr) {
        if (!$circlestr) return null;

        //Why are we matching on url when we have the exact values in circlestr??????
        $url = request()->fullUrl();
        $circle = preg_match('/circle=(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{1,3}(\.\d{0,20})?)%2C(-?\d{0,20}(\.\d{0,20})?)/', $url, $reg_out);

        //$values = explode(",", $circlestr);

        if(sizeOf($reg_out) != 6 && sizeOf($reg_out) != 7) return null;  //8 if final float has no decimals, 9 if it does

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
    function dateSearch($datefrom, $dateto, $start, $end) {
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
    public static function paginate($items, $perPage = 15, $page = null) {
	$pageName = 'page';
        $page     = $page ?: (Paginator::resolveCurrentPage($pageName) ?: 1);
        $items    = $items instanceof Collection ? $items : Collection::make($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path'     => Paginator::resolveCurrentPath(),
                'pageName' => $pageName,
            ]
        );
    }

    /* File Formatting related helper functions all moved to App/Http/Helpers/FileFormatter.php */

}
