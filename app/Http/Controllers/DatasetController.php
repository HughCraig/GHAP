<?php

namespace TLCMap\Http\Controllers;

use Illuminate\Support\Collection;
use TLCMap\Http\Helpers\UID;
use TLCMap\Models\Dataset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Response;
use DOMDocument;


class DatasetController extends Controller
{
    /********************************/
    /*   PUBLIC DATASET FUNCTIONS   */
    /********************************/

    /**
     * View all public datasets
     * @return view with all returned datasets
     */
    public function viewPublicDatasets(Request $request)
    {
        $datasets = Dataset::where('public', 1)->get();
        return view('ws.ghap.publicdatasets', ['datasets' => $datasets]);
    }

    /**
     * View a specific public dataset by id, if it exists and is public
     * If dataset does not exist with this id OR it is not public, @return redirect to viewPublicDatasets
     * else @return view with this dataset
     *
     */
    public function viewPublicDataset(Request $request, int $id)
    {
        $ds = Dataset::where(['public' => 1, 'id' => $id])->first(); // get this dataset by id if it is also public
        if (!$ds) return redirect()->route('publicdatasets'); // if not found redirect back
        return view('ws.ghap.publicdataset', ['ds' => $ds]); // if found return it with the next view
    }

    /**
     * View a public dataset as a KML
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype, or redirect to public datasets page if not found
     */
    public function viewPublicKML(Request $request, int $id)
    {
        $dataset = Dataset::where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('publicdatasets'); //redirect if not found (invalid id or not public)
        return Response::make($this->generateKML($request, $dataset), '200', array('Content-Type' => 'text/xml')); //generate the KML response
    }

    /**
     * Download a public dataset as a KML
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicKML(Request $request, int $id)
    {
        $dataset = Dataset::where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('publicdatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($this->generateKML($request, $dataset), '200', array('Content-Type' => 'text/xml', 'Content-Disposition' => 'attachment; filename="' . $filename . '.kml"'));
    }

    /**
     * View a public dataset as a GeoJSON
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype, or redirect to public datasets page if not found
     */
    public function viewPublicJSON(Request $request, int $id)
    {

        $dataset = Dataset::where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('publicdatasets'); //redirect if not found (invalid id or not public)
        return Response::make($this->generateJSON($request, $dataset), '200', array('Content-Type' => 'application/json')); //generate the json response
    }

    /**
     * Download a public dataset as a GeoJSON
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicJson(Request $request, int $id)
    {
        $dataset = Dataset::where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('publicdatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($this->generateJSON($request, $dataset), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    /**
     * View a public dataset as a CSV
     * Calls private function to generate CSV for a dataset
     * @return Response with CSV datatype, or redirect to public datasets page if not found
     */
    public function viewPublicCSV(Request $request, int $id)
    {
        $dataset = Dataset::where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('publicdatasets'); //redirect if not found (invalid id or not public)
        return Response::make($this->generateCSV($request, $dataset), '200', array('Content-Type' => 'text/csv')); //generate the CSV response
    }

    /**
     * Download a public dataset as a CSV
     * Calls private function to generate CSV for a dataset
     * @return Response with CSV datatype AND download header, or redirect to public datasets page if not found
     */
    public function downloadPublicCSV(Request $request, int $id)
    {
        $dataset = Dataset::where(['public' => 1, 'id' => $id])->first(); //Get the first dataset with this id that is 'public', if it exists
        if (!$dataset) return redirect()->route('publicdatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($this->generateCSV($request, $dataset), '200', array('Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '.CSV"'));
    }
    /********************************/
    /*   PRIVATE DATASET FUNCTIONS  */
    /********************************/

    /**
     * View a private user dataset as a KML (if allowed)
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype or redirect to user datasets page if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function viewPrivateKML(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        return Response::make($this->generateKML($request, $dataset), '200', array('Content-Type' => 'text/xml'));
    }

    /**
     * Download the private user dataset as a KML (if allowed)
     * Calls private function to generate KML for a dataset
     * @return Response with KML datatype AND download header or redirect to user datasets page if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function downloadPrivateKML(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($this->generateKML($request, $dataset), '200', array('Content-Type' => 'text/xml', 'Content-Disposition' => 'attachment; filename="' . $filename . '.kml"'));
    }

    /**
     * View a private user dataset as a GeoJSON (if allowed)
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype or redirect to user datasets if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function viewPrivateJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        return Response::make($this->generateJSON($request, $dataset), '200', array('Content-Type' => 'application/json'));
    }

    /**
     * Download the private user dataset as a GeoJSON (if allowed)
     * Calls private function to generate GeoJSON for a dataset
     * @return Response with GeoJSON datatype AND download header or redirect to user datasets if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function downloadPrivateJSON(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($this->generateJSON($request, $dataset), '200', array('Content-Type' => 'application/json', 'Content-Disposition' => 'attachment; filename="' . $filename . '.json"'));
    }

    public function viewPrivateCSV(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        return Response::make($this->generateCSV($request, $dataset), '200', array('Content-Type' => 'text/csv'));
    }

    /**
     * Download the private user dataset as a CSV (if allowed)
     * Calls private function to generate CSV for a dataset
     * @return Response with CSV datatype AND download header or redirect to user datasets if not found (or not authorized)
     *      - if not logged in the auth middleware will redirect to login (specified at route config)
     */
    public function downloadPrivateCSV(Request $request, int $id)
    {
        $user = auth()->user();
        $dataset = $user->datasets()->find($id); //Search for this dataset id ONLY within datasets associated with this user
        if (!$dataset) return redirect('myprofile/mydatasets');
        $filename = 'TLCMLayer_' . $id;
        return Response::make($this->generateCSV($request, $dataset), '200', array('Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="' . $filename . '.CSV"'));
    }

    /********************************/
    /*   DATASET TO FILE FUNCTIONS  */
    /********************************/

    /**
     * Return a generated kml
     * We dont need to generate the extended data again as exports from Gaz search do this for us
     */
    function generateKML(Request $request, $dataset)
    {

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $parNode = $dom->appendChild($dom->createElementNS('http://earth.google.com/kml/2.2', 'kml'));
        $parNode->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:gx', 'http://www.google.com/kml/ext/2.2'); //google extensions for kml eg gx:Track
        $docNode = $parNode->appendChild($dom->createElement('Document'));

        //get all dataitems for this dataset as eloquent collection
        $dataitems = $dataset->dataitems;

        //TODO: Add all of the dataset data that we need here (name, style, etc)
        //name
        $docNode->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection($dataset->name));
        //description
        $docNode->appendChild($dom->createElement('description'))->appendChild($dom->createCDATASection($dataset->description));
        //ghap_url
        $doc_ed = $docNode->appendChild($dom->createElement('ExtendedData'));
        $ghap_url = $doc_ed->appendChild($dom->createElement('Data'));
        $ghap_url->setAttribute('name', "ghap_url");
        //$ghap_url->appendChild($dom->createCDATASection($request->url()));
        $ghap_url->appendChild($dom->createCDATASection(url("publicdatasets/" . $dataset->id)));
        //style
        if (!empty($dataset->kml_style)) {
            $f = $dom->createDocumentFragment();
            $f->appendXML($dataset->kml_style); //styleUrl as raw XML (via document fragment)
            $docNode->appendChild($f);
        }


// if there is no style create a TLCMap default style. (motivated by ugly icons in TE due to Cesium default icon)


        if (!empty($dataset->kml_style)) {
            $styling = $dataset->kml_style;

        } else {
            $styling = '<Style id="TLCMapStyle">
                <IconStyle>
                <scale>1</scale>
                <Icon>
                  <href>https://tlcmap.org/img/mapicons/dotorangepip1.png</href>
                </Icon>
                </IconStyle>
                </Style>';
        }
        $f = $dom->createDocumentFragment();
        $f->appendXML($styling); //styleUrl as raw XML (via document fragment)
        $docNode->appendChild($f);


        //journey (at the very end)

        /*
            Iterate results - This is far different to the Gazetteer version ,
                as we do not build description html table or ExtendedData - it already exists (if kml was generated by tlcmap systems)
                We do not needs the anps_sources loop either - dataitems simply have a 'source'
        */
        foreach ($dataitems as $i) {
            //Setup
            $place = $docNode->appendChild($dom->createElement('Placemark'));
            $point = $place->appendChild($dom->createElement('Point'));

            // if title is empty, default to the placename. There should always be a title, but not necessarily a placename.
            if ($i->title === NULL) {
                $i->title = $i->placename;
            }
            //Minimum Data
            $place->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection($i->title));


            // if there is no style, use TLCMap default style
            if (!empty($i->kml_style_url)) {
                $kmlstyleid = $i->kml_style_url;
            } else {
                $kmlstyleid = "<styleUrl>#TLCMapStyle</styleUrl>";
            }
            $f = $dom->createDocumentFragment();
            $f->appendXML($kmlstyleid); //styleUrl as raw XML (via document fragment)
            $place->appendChild($f);


            $description = $place->appendChild($dom->createElement('description'));

            // build up description content as it may contain various parts we want
            $warning = "";
            // if there is a content warning add it to each node so it appears when the pop up does
            if ($dataset->warning !== null) {
                $warning = "<p class='tlcmwarning'>" . $dataset->warning . "</p>";
            } else {
                $warning = "";
            }

            $descriptionContent = $warning . $i->description . "
			<p><a href='" . url("search?id=" . UID::create($i->id, 't')) . "'>TLCMap</a></p>
			<p><a href='" . url("publicdatasets/" . $dataset->id) . "'>TLCMap Layer</a></p>";

            $description->appendChild($dom->createCDATASection($descriptionContent));
            $point->appendChild($dom->createElement('coordinates', $i->longitude . ',' . $i->latitude));

            //Get Timespan if one of the values exists
            if (!empty($i->datestart) || !empty($i->dateend)) {
                $timespan = $place->appendChild($dom->createElement('TimeSpan'));
                $timespan->appendChild($dom->createElement('begin', $i->datestart));
                $timespan->appendChild($dom->createElement('end', $i->dateend));
            }

            /**
             * Get ExtendedData by raw xml - TODO: We might need to generate this again?
             *      Eg user generates a KML from Gaz search results
             *          Gaz KML generator puts all the data into ExtendedData
             *          We import that into a dataset and the ExtendedData is stored as a raw XML string which we then grab here on Export from dataset
             *              HOWEVER if the user changes values in the dataset, the ExtendedData doesn't update to reflect this!!!!
             */
            if (!empty($i->extended_data)) {
                $f = $dom->createDocumentFragment();
                $f->appendXML($i->extended_data); //extended_date as raw XML
                $place->appendChild($f);
            }

        }

        //journey
        if (!empty($dataset->kml_journey)) {
            $place = $docNode->appendChild($dom->createElement('Placemark'));
            $sxe = simplexml_load_string($dataset->kml_journey, "SimpleXMLElement", LIBXML_NOERROR, "gx:Track"); //load the string in as a simplexml element IGNORE ERRORS
            $dom_sxe = dom_import_simplexml($sxe);
            $dom_sxe = $dom->importNode($dom_sxe, true);
            $place->appendChild($dom_sxe);
        }

        return $dom->saveXML();
    }

    /**
     * Return a generated json
     * TODO: Extended data is raw KML from DB - this is not ideal for json output
     * json_encode automatically handles escaping quotes, etc
     *
     * Could be merged with FileFormatter->toGeoJSON
     *
     * Return a line instead of the full data collection. This is mainly for the visualisation, to add a line to a journey.
     * options are to order it by order in the database, or sort by date.
     * These are set in the query string ?line=route or ?line=time
     */
    function generateJSON(Request $request, $dataset)
    {
        $features = array();
        $metadata = array(
            'layerid' => $dataset->id,
            'name' => $dataset->name,
            'description' => $dataset->description,
            'warning' => $dataset->warning,
            'ghap_url' => $request->url(),
            'linkback' => $dataset->linkback,
        );

        // Infill any blank start/end dates.
        $dataitems = $this->infillDataitemDates($dataset->dataitems);

        if (isset($_GET["sort"])) {
            // $dataitems = $dataset->dataitems()->whereNotNull("enddate")->whereNotNull("datestart");

            $dataitems = $dataitems->where('datestart', '!==', '')->where('dateend', '!==', '');

            if ($_GET["sort"] === 'end') {
                $dataitems = $dataitems->sortBy('dateend')->values()->all();
            } else {
                $dataitems = $dataitems->sortBy('datestart')->values()->all();
            }
        }

        if (isset($_GET["line"])) {


            if ($_GET["line"] === 'time') {
                $dataitems = $dataitems->sortBy('datestart')->values()->all();
                // Log::debug('An informational message.' . json_encode($dataset->dataitems, JSON_PRETTY_PRINT));
            }


            $linecoords = array();

            foreach ($dataitems as $i) {
                array_push($linecoords, [$i->longitude, $i->latitude]);
            }
            $proppairs["name"] = $dataset->name;
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'LineString', 'coordinates' => $linecoords),
                'properties' => $proppairs
            );


            $allfeatures = array('type' => 'FeatureCollection', 'metadata' => $metadata, 'features' => $features);
            return json_encode($allfeatures, JSON_PRETTY_PRINT);
        }

        if (isset($_GET["metadata"])) {
            $allfeatures = array('metadata' => $metadata);
            return json_encode($allfeatures, JSON_PRETTY_PRINT);
        }


        foreach ($dataitems as $i) {
            //$id = 'Dataitem ' . base_convert($i->dataitem_id,10,16);

            // we are sorting by dates in a context that requires dates, such as the timeline maps, so we can't include null or empty dates.
            if (isset($_GET["sort"])) {
                if (empty($i->datestart) && empty($i->dateend)) {
                    continue;
                }
                // if only one of them is filled, we need to populate with the other
            }


            $contribution = 'Contribution: "' . $dataset->name . '"<br> by User: ' . $dataset->ownerName();


            $proppairs = array();
            if (!empty($i->title)) {
                $proppairs["name"] = $i->title;
            } else {
                $proppairs["name"] = $i->placename;
            }
            if (!empty($i->placename)) {
                $proppairs["placename"] = $i->placename;
            }
            if (!empty($i->description)) {
                $proppairs["description"] = $i->description;
            }
            if (!empty($i->id)) {
                $proppairs["id"] = UID::create($i->id);
            }
            if (!empty($i->warning)) {
                $proppairs["warning"] = $i->warning;
            }
            if (!empty($i->state)) {
                $proppairs["state"] = $i->state;
            }
            if (!empty($i->parish)) {
                $proppairs["parish"] = $i->parish;
            }
            if (!empty($i->feature_term)) {
                $proppairs["feature_term"] = $i->feature_term;
            }
            if (!empty($i->lga)) {
                $proppairs["lga"] = $i->lga;
            }
            if (!empty($i->source)) {
                $proppairs["source"] = $i->source;
            }
            //	if (!empty($contribution)){$proppairs["contribution"] = $contribution;}

            if (!empty($i->datestart)) {
                $proppairs["datestart"] = $i->datestart;
            }
            if (!empty($i->dateend)) {
                $proppairs["dateend"] = $i->dateend;
            }

            // geojson layers in arcgis. this was marked as for testing only to be removed, but I think it turned out to be necessary, so keep
            $unixepochdates = $i->datestart . "";
            $unixepochdatee = $i->dateend . "";
            //$unixepochdates = preg_replace("/[^0-9\-]/", "", $unixepochdates );
            if (strpos($unixepochdates, '-') === false) {
                $unixepochdates = $unixepochdates . "-01-01";
            }
            if (strpos($unixepochdatee, '-') === false) {
                $unixepochdatee = $unixepochdatee . "-01-01";
            }
            //   if (!empty($i->datestart)){$proppairs["time"] = strtotime($unixepochdates) ;}

            if (!empty($i->datestart)) {
                $proppairs["udatestart"] = strtotime($unixepochdates) * 1000;
            }
            if (!empty($i->dateend)) {
                $proppairs["udateend"] = strtotime($unixepochdates) * 1000;
            }

            // if we are sorting by date, we are in a context like timeline where we can't have null dates.
            if (isset($_GET["sort"])) {
                // if only one of them is filled, we need to populate with the other
                if (empty($i->datestart) && !empty($i->dateend)) {
                    $proppairs["datestart"] = $i->dateend;
                    $proppairs["udatestart"] = $proppairs["udateend"];
                }
                if (empty($i->dateend) && !empty($i->datestart)) {
                    $proppairs["dateend"] = $i->datestart;
                    $proppairs["udateend"] = $proppairs["udatestart"];
                }
            }


            if (!empty($i->latitude)) {
                $proppairs["latitude"] = $i->latitude;
            }
            if (!empty($i->longitude)) {
                $proppairs["longitude"] = $i->longitude;
            }

            if (!empty($i->external_url)) {
                $proppairs["linkback"] = $i->external_url;
            }
            if (!empty($i->extended_data)) {
                $proppairs = array_merge($proppairs, $i->extDataAsKeyValues());
                //$proppairs["extended_data"] = $i->extDataAsHTML();
            }

            $proppairs["TLCMapLinkBack"] = url("search?id=" . UID::create($i->id, 't'));
            $proppairs["TLCMapDataset"] = url("publicdatasets/" . $dataset->id);


            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => array((float)$proppairs["longitude"], (float)$proppairs["latitude"])),
                'properties' => $proppairs
            );
        }

        $allfeatures = array('type' => 'FeatureCollection', 'metadata' => $metadata, 'features' => $features);
        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }

    /**
     * Infill start/end dates for dataitems.
     *
     * This method will infill the empty start/end date of a dataitem with its end/start date.
     *
     * @param Collection $items
     *   The dateitems.
     * @return Collection
     *   The dateitems with dates infilled.
     */
    private function infillDataitemDates($items) {
        foreach ($items as &$item) {
            if (!empty($item->datestart) && empty($item->dateend)) {
                $item->dateend = $item->datestart;
            } elseif (empty($item->datestart) && !empty($item->dateend)) {
                $item->datestart = $item->dateend;
            }
        }
        return $items;
    }

    /**
     * Return a generated csv
     * TODO: Extended data is raw KML from DB - this is not ideal for csv output
     * csv_encode automatically handles escaping quotes, etc
     *
     * Could be merged with FileFormatter->toGeoCSV
     */
    function generateCSV(Request $request, $dataset)
    {
        //    $features = array();
        //    $metadata = array('name' => $dataset->name, 'description' => $dataset->description, 'ghap_url' => $request->url());

        //get all dataitems for this dataset as eloquent collection


        $f = fopen('php://memory', 'r+');
        $delimiter = ',';
        $enclosure = '"';
        $escape_char = "\\";


        /*
      foreach ($test as $item) {
          fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
      }
      rewind($f);
      return stream_get_contents($f);
      */


        $dataitems = $dataset->dataitems;

        // $f = fopen('php://output', 'w');

        // SET UP HEADERS, do the preprocess. Need to loop all extended data to ensure all columns obtained.
        //fputcsv($f, array_keys($proppairs[0])); // Add the keys as the column headers

        // $headers = array("id", "title", "placename", "latitude", "longitude", "description", "warning", "state", "parish", "feature_term", "lga", "source", "datestart", "dateend", "external_url", "TLCMapLinkBack", "TLCMapDataset");
        $colheads = array();
        $extkeys = array();
// !!!!!!!!!! actually also go through the headers and only put them in if at least one is not null.....

//Log::error(json_encode($dataitems[0]));

// Fudge to convert object with properties to key value pairs
        $arr = json_decode(json_encode($dataitems[0]), true);

// Loop through the associative array
//foreach($arr as $key=>$value){
//    Log::error($key . " => " . $value . "<br>");
//}


        foreach ($dataitems[0] as $key => $value) {
            //  Log::error( "$key => $value\n");
        }

        foreach ($dataitems as $i) {

            // only headers with values
            // must be an easier way than this but
            // Fudge to convert object with properties to key value pairs
            $arr = json_decode(json_encode($i), true);
            foreach ($arr as $key => $value) {
                if (!($value === NULL) && $key !== 'extended_data') {
                    if (!in_array($key, $colheads)) {
                        $colheads[] = $key;
                    }
                }
            }
        }
        foreach ($dataitems as $i) {

            // add extended data headers
            $arr = $i->getExtendedData();
            if (!empty($arr)) {
                foreach ($arr as $key => $value) {
                    if (!($value === NULL)) {
                        if (!in_array($key, $colheads)) {
                            $colheads[] = $key;
                        }
                    }
                }
                //array_unique(array_merge($extkeys, array_keys($i->extDataAsKeyValues())));
            }
        }
        $colheads = array_merge($colheads, $extkeys);

        // Apply any modification to the column headers for display.
        $displayHeaders = [];
        foreach ($colheads as $colhead) {
            if ($colhead === 'id') {
                $displayHeaders[] = 'ghap_id';
            } else {
                $displayHeaders[] = $colhead;
            }
        }

// add headings to csv
        fputcsv($f, $displayHeaders, $delimiter, $enclosure, $escape_char);

// now the data
        foreach ($dataitems as &$i) {

            $cells = array();

            $vals = json_decode(json_encode($i), true);

            $ext = $i->getExtendedData();
            if (!empty($ext)) {
                $vals = $vals + $ext;
            }

            $vals["id"] = UID::create($vals["id"], 't');

            // to make sure the cells are in the same order as the headings
            foreach ($colheads as $col) {
                $cells[] = isset($vals[$col]) ? $vals[$col] : "";
                //$cells[] = $vals[$col];
            }


            fputcsv($f, $cells, $delimiter, $enclosure, $escape_char);


        }
        rewind($f);
//        $allfeatures = array('type' => 'FeatureCollection', 'metadata' => $metadata, 'features' => $features);

        // Loop over the array and passing in the values only.


        return stream_get_contents($f);;
    }

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
     * @param  \Illuminate\Http\Request $iequest
     * @return \Illuminate\Http\Response
     */
    public function store(Request $iequest)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \TLCMap\Dataset $ds
     * @return \Illuminate\Http\Response
     */
    public function show(Dataset $ds)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \TLCMap\Dataset $ds
     * @return \Illuminate\Http\Response
     */
    public function edit(Dataset $ds)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $iequest
     * @param  \TLCMap\Dataset $ds
     * @return \Illuminate\Http\Response
     */
    public function update(Request $iequest, Dataset $ds)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \TLCMap\Dataset $ds
     * @return \Illuminate\Http\Response
     */
    public function destroy(Dataset $ds)
    {
        //
    }
}
