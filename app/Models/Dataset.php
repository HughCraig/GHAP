<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;
use TLCMap\Http\Helpers\HtmlFilter;
use TLCMap\ViewConfig\FeatureCollectionConfig;
use TLCMap\ViewConfig\FeatureConfig;
use TLCMap\ViewConfig\GhapConfig;
use TLCMap\Models\RecordType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Dataset extends Model
{
    protected $table = "tlcmap.dataset";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'id', 'name', 'description', 'creator', 'public', 'allowanps', 'publisher', 'contact', 'citation', 'doi',
        'source_url', 'linkback', 'latitude_from', 'longitude_from', 'latitude_to', 'longitude_to', 'language', 'license', 'rights',
        'temporal_from', 'temporal_to', 'created', 'kml_style', 'kml_journey', 'recordtype_id', 'warning' , 'image_path'
    ];

    /**
     * Define a user relationship
     * 1 user has many datasets, many datasets have many users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'tlcmap.user_dataset')->withPivot('id', 'user_id', 'dsrole', 'dataset_id', 'created_at', 'updated_at');
    }

    public function owner()
    {
        return $this->users()->where('dsrole', 'OWNER')->first()->id;
    }

    public function ownerName()
    {
        return $this->users()->where('dsrole', 'OWNER')->first()->name;
    }

    public function recordtype()
    {
        return $this->belongsTo(RecordType::class, 'recordtype_id');
    }

    public function subjectKeywords()
    {
        return $this->belongsToMany(SubjectKeyword::class, 'tlcmap.dataset_subject_keyword')->withPivot('dataset_id', 'subject_keyword_id');
    }

    /**
     * Fetch all public layers/datasets along with their IDs
     *
     * @return array
     *   An array of objects with 'id' and 'name' properties.
     */
    public static function getAllPublicLayersAndIDs()
    {
        $layers = self::where('public', 1)->select('id', 'name')->get();

        return $layers->map(function($layer) {
            return (object) [
                'id' => $layer->id,
                'name' => $layer->name
            ];
        })->all();
    }

    /**
     * Defines a dataitem relationship
     * 1 dataset has many dataitems
     */
    public function dataitems()
    {
        return $this->hasMany(Dataitem::class);
    }

    /**
     * Defines a Collab Link relationship
     * 1 dataset has many colllablinks
     */
    public function collablinks()
    {
        return $this->hasMany(CollabLink::class);
    }

    /**
     * The collections that belong to the dataset.
     */
    public function collections()
    {
        return $this->belongsToMany('TLCMap\Models\Collection', 'tlcmap.collection_dataset', 'dataset_id', 'collection_id');
    }

    public function addData($data)
    {
        if (is_array($data)) return $this->addDataItems($data);
        return $this->addDataItem($data);
    }

    /*
        Adds a single data item
    */
    public function addDataItem($dataitem)
    {
        Dataitem::create([
            'title' => $dataitem->title,
            'latitude' => $dataitem->latitude,
            'longitude' => $dataitem->longitude
        ]);
    }

    /*
        Adds a collection of dataitems
    */
    public function addDataItems($dataitems)
    {
        foreach ($dataitems as $dataitem) {
            $this->addDataItem($dataitem);
        }
    }

    // event handler to delete this dataset's dataitems when this is deleted (untested)
    public static function boot()
    {
        parent::boot();
        self::deleting(function ($dataset) { // before delete() method call this
            $dataset->dataitems()->each(function ($dataitem) {
                $dataitem->delete();
            });
        });
    }

    /**
     * Generate CSV of the dataset.
     *
     * Return a generated csv
     * TODO: Extended data is raw KML from DB - this is not ideal for csv output
     * csv_encode automatically handles escaping quotes, etc
     *
     * Could be merged with FileFormatter->toGeoCSV
     *
     * @return string
     *   The generated CSV.
     */
    public function csv()
    {
        $dataset = $this;
        $f = fopen('php://memory', 'r+');
        $delimiter = ',';
        $enclosure = '"';
        $escape_char = "\\";
        // Exclude some columns.
        $excludeColumns = ['uid', 'datasource_id'];

        $dataitems = $dataset->dataitems;
        $colheads = array();
        $extkeys = array();
        // !!!!!!!!!! actually also go through the headers and only put them in if at least one is not null.....

        // Fudge to convert object with properties to key value pairs
        $arr = json_decode(json_encode($dataitems[0]), true);

        foreach ($dataitems as $i) {

            // only headers with values
            // must be an easier way than this but
            // Fudge to convert object with properties to key value pairs
            $arr = json_decode(json_encode($i), true);
            foreach ($arr as $key => $value) {
                if (!($value === NULL) && $key !== 'extended_data') {
                    if (!in_array($key, $colheads) && !in_array($key, $excludeColumns)) {
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
            }
        }
        $colheads = array_merge($colheads, $extkeys);

        // Apply any modification to the column headers for display.
        $headerValueForDisplay = [ 'id' => 'ghap_id' , 'external_url' => 'linkback' , 'dataset_id' => 'layer_id' , 'recordtype_id' => 'record_type' ];
        $displayHeaders = [];
        foreach ($colheads as $colhead) {
            $displayHeaders[] = isset($headerValueForDisplay[$colhead]) ? $headerValueForDisplay[$colhead] : $colhead;
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

            $vals["id"] = $i->uid;

            // to make sure the cells are in the same order as the headings
            foreach ($colheads as $col) {
                $cellValue = isset($vals[$col]) ? $vals[$col] : "";

                // Special handling for recordtype, store type instead of id
                if ( $cellValue !== "" && $col === 'recordtype_id') {
                    $cellValue = RecordType::getTypeById($cellValue);
                }

                $cells[] = $cellValue;
            }


            fputcsv($f, $cells, $delimiter, $enclosure, $escape_char);


        }
        rewind($f);

        // Loop over the array and passing in the values only.


        return stream_get_contents($f);
    }

    /**
     * Generate the KML of the dataset.
     *
     * We dont need to generate the extended data again as exports from Gaz search do this for us.
     *
     * @return string
     *   The generated KML.
     */
    public function kml()
    {
        $dataset = $this;
        $dom = new \DOMDocument('1.0', 'UTF-8');
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
			<p><a href='" . url("search?id=" . $i->uid) . "'>TLCMap</a></p>
			<p><a href='" . url("publicdatasets/" . $dataset->id) . "'>TLCMap Layer</a></p>";

            $description->appendChild($dom->createCDATASection($descriptionContent));
            $point->appendChild($dom->createElement('coordinates', $i->longitude . ',' . $i->latitude));

            //Get Timespan if one of the values exists
            if (!empty($i->datestart) || !empty($i->dateend)) {
                $timespan = $place->appendChild($dom->createElement('TimeSpan'));
                $timespan->appendChild($dom->createElement('begin', $i->datestart));
                $timespan->appendChild($dom->createElement('end', $i->dateend));
            }

            // Get quantity if it exists
            if (!empty($i->quantity)) {
                $place->appendChild($dom->createElement('quantity', $i->quantity));
            }

             //Get RouteInfo if they exist
             if (!empty($i->route_id)) {
                $RouteInfo = $place->appendChild($dom->createElement('RouteInfo'));
                $RouteInfo->appendChild($dom->createElement('routeId', $i->route_id));
                if (!empty($i->route_original_id)) {
                    $RouteInfo->appendChild($dom->createElement('routeOriginalId', $i->route_original_id));
                }
                if (!empty($i->route_title)) {
                    $RouteInfo->appendChild($dom->createElement('routeTitle', $i->route_title));
                }
                if (!empty($i->stop_idx)) {
                    $RouteInfo->appendChild($dom->createElement('routeStopNum', $i->stop_idx));
                }
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
     * Generate the GeoJSON of the dataset.
     *
     * Return a generated json
     * TODO: Extended data is raw KML from DB - this is not ideal for json output
     * json_encode automatically handles escaping quotes, etc
     *
     * Could be merged with FileFormatter->toGeoJSON
     *
     * Return a line instead of the full data collection. This is mainly for the visualisation, to add a line to a journey.
     * options are to order it by order in the database, or sort by date.
     * These are set in the query string ?line=route or ?line=time
     *
     * @return string
     *   The generated GeoJSON.
     */
    public function json()
    {
        $dataset = $this;
        $features = array();

        $metadata = array(
            'layerid' => $dataset->id,
            'name' => $dataset->name,
            'description' => $dataset->description,
            'warning' => $dataset->warning,
            'ghap_url' => $dataset->public ? url("publicdatasets/{$dataset->id}") : url("myprofile/mydatasets/{$dataset->id}"),
            'linkback' => $dataset->linkback
        );

        // Set the feature collection config.
        $featureCollectionConfig = new FeatureCollectionConfig();
        $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
        $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());
        $featureCollectionConfig->setInfoTitle($metadata['name'], $metadata['ghap_url']);
        $featureCollectionConfig->setInfoContent(GhapConfig::createDatasetInfoBlockContent($dataset));

        // Infill any blank start/end dates.
        $dataitems = self::infillDataitemDates($dataset->dataitems);

        if (isset($_GET["sort"])) {
            $dataitems = $dataitems->where('datestart', '!==', '')->where('dateend', '!==', '');

            if ($_GET["sort"] === 'end') {
                $dataitems = $dataitems->sortBy('dateend')->values()->all();
            } else {
                $dataitems = $dataitems->sortBy('datestart')->values()->all();
            }
        }

        if (isset($_GET["metadata"])) {
            $allfeatures = array('metadata' => $metadata);
            return json_encode($allfeatures, JSON_PRETTY_PRINT);
        }

        // Calculate the 4-quantitles of log of quantity (by default)
        $quantityValues = array();

        foreach ($dataitems as $i) {

            // we are sorting by dates in a context that requires dates, such as the timeline maps, so we can't include null or empty dates.
            if (isset($_GET["sort"])) {
                if (empty($i->datestart) && empty($i->dateend)) {
                    continue;
                }
                // if only one of them is filled, we need to populate with the other
            }

            // Set feature config.
            $featureConfig = new FeatureConfig();
            $ismultiroute = FALSE;

            $proppairs = array();

            if (!empty($i->image_path)) {
                $imageUrl = Storage::disk('public')->url('images/' . $i->image_path);
                $proppairs["Image"] = '<img src="' . $imageUrl . '" alt="Place Image">';
            }

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
            if (!empty($i->quantity)) {
                $proppairs["quantity"] = $i->quantity;
                $quantityValues[] = $i->quantity; // Add quantity to the quantityValues array
                $proppairs["logQuantity"] = round(log($i->quantity), 2);
            }
            if (!empty($i->route_id)) {
                $proppairs["route_id"] = $i->route_id;
                $ismultiroute = TRUE;
            }
            if (!empty($i->route_title)) {
                $proppairs["route_title"] = $i->route_title;
            }
            if (!empty($i->route_description)) {
                $proppairs["route_description"] = $i->route_description;
            }
            if (!empty($i->stop_idx)) {
                $proppairs["stop_idx"] = $i->stop_idx;
            }
            if (!empty($i->id)) {
                $proppairs["id"] = $i->uid;
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

            if (!empty($i->datestart)) {
                $proppairs["datestart"] = $i->datestart;
            }
            if (!empty($i->dateend)) {
                $proppairs["dateend"] = $i->dateend;
            }

            // geojson layers in arcgis. this was marked as for testing only to be removed, but I think it turned out to be necessary, so keep
            $unixepochdates = $i->datestart . "";
            $unixepochdatee = $i->dateend . "";
            if (strpos($unixepochdates, '-') === false) {
                $unixepochdates = $unixepochdates . "-01-01";
            }
            if (strpos($unixepochdatee, '-') === false) {
                $unixepochdatee = $unixepochdatee . "-01-01";
            }

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

            if (isset($i->latitude)) {
                $proppairs["latitude"] = $i->latitude;
            }
            if (isset($i->longitude)) {
                $proppairs["longitude"] = $i->longitude;
            }

            if (!empty($i->external_url)) {
                $proppairs["linkback"] = $i->external_url;
            }else if(!empty($dataset->linkback)){
                $proppairs["linkback"] = $dataset->linkback;
            }

            if (!empty($i->extended_data)) {
                $proppairs = array_merge($proppairs, $i->extDataAsKeyValues());
                //$proppairs["extended_data"] = $i->extDataAsHTML();
            }

            $proppairs["TLCMapLinkBack"] = url("search?id=" . $i->uid);
            $proppairs["TLCMapDataset"] = $metadata['ghap_url'];

            file_put_contents('test.log', var_export($i, true), FILE_APPEND);

            // Set footer links.
            $featureConfig->addLink("TLCMap Record: {$i->uid}", $proppairs["TLCMapLinkBack"]);
            $featureConfig->addLink('TLCMap Layer', $proppairs["TLCMapDataset"]);

            if (isset($proppairs["longitude"]) && isset($proppairs["latitude"])) {
                $features[] = array(
                    'type' => 'Feature',
                    'geometry' => array('type' => 'Point', 'coordinates' => array((float)$proppairs["longitude"], (float)$proppairs["latitude"])),
                    'properties' => $proppairs,
                    'display' => $featureConfig->toArray(),
                );
            }
        }

        // Include the lines features if the query string has the parameter "line".
        if (isset($_GET["line"])) {
            if ($_GET["line"] === 'time') {
                $dataitems = $dataitems->sortBy('datestart')->values()->all();
            }

            $linecoords = array();

            foreach ($dataitems as $i) {
                array_push($linecoords, [$i->longitude, $i->latitude]);
            }

            // Set line feature config.
            $featureConfig = new FeatureConfig();
            $featureConfig->setAllowedFields([]);

            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'LineString', 'coordinates' => $linecoords),
                'properties' => ['name' => $dataset->name],
                'display' => $featureConfig->toArray(),
            );
        }
        /**
         * Include the another type of polyline feature if the query string has the parameter "mobility".
         *
         * This is a place holder for mobility geojson generated geojson.
         * Only an enhanced version of journey geojson now.
         *
         * TODO:
         * 1. [DONE] add quantile of the log of quantity
         * 2. The polyline for "LineString" feature consist of a collection of (connected 2-point) lines, but not a collection of points.
         * The polyline still represents a single line now.
         * 1. The polyline for "LineString" feature should be extend to a multiple line segments
         * that can handle "JourneyID" later.
        */
        if (isset($_GET["mobility"])) {

            // Set line feature config.
            $featureConfig = new FeatureConfig();
            $featureConfig->setAllowedFields([]);

            $routeGroups = [];
            // $ismultiroute = FALSE;
            if ($ismultiroute === TRUE) {
                foreach ($dataitems as $i) {
                    $routeId = $i->route_id;
                    if (!isset($routeGroups[$routeId])) {
                        $routeGroups[$routeId] = [];
                    }
                    $routeGroups[$routeId][] = $i;
                }
                // Process each route group
                foreach ($routeGroups as $routeId => $items) {
                    // Sort items within each route group by stop_idx
                    usort($items, function ($a, $b) {
                        return $a->stop_idx - $b->stop_idx;
                    });

                    $routeCoords = [];
                    foreach ($items as $item) {
                        $routeCoords[] = [$item->longitude, $item->latitude];
                    }
                    // Initialize properties with default values
                    $defaultRouteDescr = "No detailed description";
                    $routeProps = [
                        'title' => null,
                        'route_id' => null,
                        'route_title' => null,
                        'route_description' => $defaultRouteDescr
                    ];

                    foreach ($items as $item) {
                        if (empty($routeProps['route_id']) && !empty($item->route_id)) {
                            $routeProps['route_id'] = $item->route_id;
                        }
                        if (empty($routeProps['route_title']) && !empty($item->route_title)) {
                            $routeProps['route_title'] = $item->route_title;
                            $routeProps['title'] = $item->route_title;
                        }
                        // Update route_description only if it's the default value
                        if ($routeProps['route_description'] === $defaultRouteDescr && !empty($item->route_description)) {
                            $routeProps['route_description'] = $item->route_description;
                        }
                    }

                    // Create a geojson feature for this route
                    $features[] = [
                        'type' => 'Feature',
                        'geometry' => [
                            'type' => 'LineString',
                            'coordinates' => $routeCoords
                        ],
                        'properties' => $routeProps
                    ];
                    file_put_contents("test.log", var_export($features, true));
                }
            } else {
                $routeData = [];

                foreach ($dataitems as $i) {
                    array_push($routeData, [$i->longitude, $i->latitude]);
                }

                $features[] = array(
                    'type' => 'Feature',
                    'geometry' => array('type' => 'LineString', 'coordinates' => $routeData),
                    'properties' => ['name' => $dataset->name],
                    'display' => $featureConfig->toArray(),
                );
            }
        }

        if (!empty($quantityValues)){
            $logQuantiles = $this->getQuantiles($quantityValues, 'log');
            $metadata['log_quantiles'] = $logQuantiles;
            $quantiles = $this->getQuantiles($quantityValues, function($x) { return $x; });
            $metadata['quantiles'] = $quantiles;

            // reset the blocked fields for mobility view
            $allowedFields = [
                'quantity', 'logQuantity',
                "stop_idx", "route_title", "route_description",
            ];
            $featureCollectionConfig->setBlockedFields(
                array_values(
                    array_diff(GhapConfig::blockedFields(), $allowedFields)
                ));
        }

        $allfeatures = array(
            'type' => 'FeatureCollection',
            'metadata' => $metadata,
            'features' => $features,
            'display' => $featureCollectionConfig->toArray(),
        );

        if( count($features) == 0){
            $allfeatures['metadata']['warning'] .=  "<p>0 results found</p>";
            $allfeatures['display']['info']['content'] .= "<div class=\"warning-message\"><p>0 results found</p></div>";
        }

        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }

    /**
     * Generate the GeoJSON when visiting a private dataset or non-exist dataset.
     * show warning message at info block
     */
    public static function getRestrictedDatasetGeoJSON(){

        $featureCollectionConfig = new FeatureCollectionConfig();
        $featureCollectionConfig->setInfoContent(GhapConfig::createRestrictedDatasetInfoBlockContent());
        $allfeatures = array(
            'type' => 'FeatureCollection',
            'metadata' => [
                'warnnig' => 'This map either does not exist or has been set to "private" and therefore cannot be displayed.'
            ],
            'display' => $featureCollectionConfig->toArray(),
            'features' => [],
        );

        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }
    /**
     * Infill start/end dates for dataitems.
     *
     * This method will infill the empty start/end date of a dataitem with its end/start date.
     *
     * @param \Illuminate\Support\Collection $items
     *   The dateitems.
     * @return \Illuminate\Support\Collection
     *   The dateitems with dates infilled.
     */
    public static function infillDataitemDates($items) {
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
     * Get public dataset by id with data items.
     *
     * If the dataset has route information, the returned dataset will be assigned a stop_idx for each
     * data item and ordered by route_id, datastart, dataset_order respectively. If not, the matching dataset
     * will be ordered by dataset_order only.
     *
     * @param int $ds_id The ID of the dataset
     * @return mixed The public dataset with associated data items
     */
    public static function getPublicDatasetById(int $ds_id)
    {
        $query = self::where(['public' => 1, 'id' => $ds_id]); // get this dataset by id if it is also public

        if (self::where(['id' => $ds_id])->whereHas('dataitems', function ($query) {
            $query->whereNotNull('route_id');
        })->exists()) {
            $dataset = $query
                ->with(['dataitems' => function ($query) {
                    $query->select('*', DB::raw('ROW_NUMBER() OVER(PARTITION BY route_id ORDER BY datestart, dataset_order) as stop_idx'))
                        ->orderBy('route_id')
                        ->orderBy('datestart')
                        ->orderBy('dataset_order');
                }])
                ->first();
        } else {
            $dataset = $query
                ->with(['dataitems' => function ($query) {
                    $query->orderBy('dataset_order');
                }])
                ->first();
        }

        return $dataset;
    }

    /**
     * Get private dataset by ID with data items
     *
     * @param User $user The user (model) object
     * @param int $ds_id The ID of the dataset
     * @return mixed The private dataset with associated data items
     */
    public static function getPrivateDatasetById($user, $ds_id)
    {
        $dataset = $user->datasets()->with(['dataitems' => function ($query) use ($ds_id) {
            // Check if any dataitem has a route_id
            if (DB::table('tlcmap.dataitem')->whereNotNull('route_id')->exists()) {
                $query->select('*', DB::raw('ROW_NUMBER() OVER(PARTITION BY route_id ORDER BY datestart, dataset_order) as stop_idx'))
                    ->orderBy('route_id')
                    ->orderBy('datestart')
                    ->orderBy('dataset_order');
            } else {
                $query->orderBy('dataset_order');
            }
        }])->find($ds_id);

        return $dataset;
    }

    /**
     * Calculate logarithmic quantiles and generate corresponding ranges for the given quantity values.
     *
     * This method calculates the logarithmic quantiles and returns an array containing quantile ranges
     * and the original quantity values along with their logarithms and quantile indices.
     *
     * @param array $quantityValues
     *   An array containing the quantity values.
     * @param int $numQuantiles
     *   The number of quantiles to be generated (default is 4).
     * @return array
     *   ...
     */
    public static function getQuantiles($quantityValues, $mapFunc, $numQuantiles = 4) {
        sort($quantityValues);
        $resultQty = array_map($mapFunc, $quantityValues);
        $count = count($resultQty);
        $quantiles = [];

        for ($i = 1; $i < $numQuantiles; $i++) {
            $position = $i * ($count - 1) / $numQuantiles;
            $lower = floor($position);
            $fraction = $position - $lower;
            $quantile = $resultQty[$lower] + $fraction * ($resultQty[$lower + 1] - $resultQty[$lower]);
            $quantiles[] = round($quantile, 2);
        }

        return $quantiles;
    }

}
