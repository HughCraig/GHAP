<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;
use TLCMap\Http\Helpers\HtmlFilter;
use TLCMap\ViewConfig\FeatureCollectionConfig;
use TLCMap\ViewConfig\FeatureConfig;
use TLCMap\ViewConfig\GhapConfig;
use TLCMap\Models\RecordType;
use TLCMap\Models\TextContext;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use TLCMap\Http\Helpers\GeneralFunctions;
use TLCMap\ViewConfig\CollectionConfig;

class Dataset extends Model
{
    protected $table = "tlcmap.dataset";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'id', 'name', 'description', 'creator', 'public', 'allowanps', 'publisher', 'contact', 'citation', 'doi',
        'source_url', 'linkback', 'latitude_from', 'longitude_from', 'latitude_to', 'longitude_to', 'language', 'license', 'rights',
        'temporal_from', 'temporal_to', 'created', 'kml_style', 'kml_journey', 'recordtype_id', 'warning' , 'image_path' , 'from_text_id', 'access_token' , 'featured_url'
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

    /**
     * Define the relationship to the Text model.
     * One Dataset belongs to one Text (linked by `from_text_id`).
     */
    public function text()
    {
        return $this->belongsTo(Text::class, 'from_text_id');
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
        // if (!empty($dataset->kml_style)) {
        //     $f = $dom->createDocumentFragment();
        //     $f->appendXML($dataset->kml_style); //styleUrl as raw XML (via document fragment)
        //     $docNode->appendChild($f);
        // }


        // if there is no style create a TLCMap default style. (motivated by ugly icons in TE due to Cesium default icon)


        // if (!empty($dataset->kml_style)) {
        //     $styling = $dataset->kml_style;
        // } else {
            $styling = '<Style id="TLCMapStyle">
                <IconStyle>
                <scale>1</scale>
                <Icon>
                  <href>https://tlcmap.org/img/mapicons/dotorangepip1.png</href>
                </Icon>
                </IconStyle>
                </Style>';
        //}
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

            $proppairs = array();

            if (!empty($i->image_path)) {
                $imageUrl = Storage::disk('public')->url('images/' . $i->image_path);
                $proppairs["Image"] = '<img src="' . $imageUrl . '" alt="Place Image">';
            }

            if(!empty($i->glycerine_url)){
                $proppairs["Glycerine"] = '<a href="' . $i->glycerine_url . '" target="_blank">Glycerine Image</a>';
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
            $udatestart = null;

            if (!empty($i->datestart)) {
                $udatestart = GeneralFunctions::dataToUnixtimestamp($i->datestart);
                $proppairs['udatestart'] = $udatestart;
            }

            if (!empty($i->dateend)) {
                $udateend = GeneralFunctions::dataToUnixtimestamp($i->dateend);
                if ($udatestart !== null && $udateend !== null && $udateend < $udatestart) {
                    $udateend = $udatestart;
                }
                $proppairs['udateend'] = $udateend;
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

            // Set footer links.
            $featureConfig->addLink("TLCMap Record: {$i->uid}", $proppairs["TLCMapLinkBack"]);
            $featureConfig->addLink('TLCMap Layer', $proppairs["TLCMapDataset"]);

            $featureConfig = $featureConfig->toArray();
            $featureConfig['source'] = [
                'TLCMapID' => [
                    'id' => $i->uid,
                    'url' => $proppairs["TLCMapLinkBack"]
                ],
                'Layer' => [
                    'name' => $this->name . ' (community contributed)',
                    'url' => $metadata['ghap_url']
                ]
            ];

            if (isset($proppairs["longitude"]) && isset($proppairs["latitude"])) {
                $features[] = array(
                    'type' => 'Feature',
                    'geometry' => array('type' => 'Point', 'coordinates' => array((float)$proppairs["longitude"], (float)$proppairs["latitude"])),
                    'properties' => $proppairs,
                    'display' => $featureConfig,
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

        $allfeatures = array(
            'type' => 'FeatureCollection',
            'metadata' => $metadata,
            'features' => $features,
            'display' => $featureCollectionConfig->toArray(),
        );

        if (isset($_GET["textmap"]) && $this->text ) {        
           $text = $this->text;
           $allfeatures['textcontent'] = ($text->content);

           foreach($dataitems as $dataitem){
              if($dataitem->recordtype_id == '4'){
                $textContext = TextContext::getContentByDataitemUid($dataitem->uid);
                if($textContext->count() > 0){
                    $textContextArray = $textContext->first()->toArray(); 
                    $textContextArray['linked_dataitem_uid'] = $dataitem->linked_dataitem_uid ? $dataitem->linked_dataitem_uid : null;            
                    $allfeatures['textcontexts'][] = $textContextArray; 
                }
              }
           }
           $allfeatures['textID'] = $text->id;
        }

        $allfeatures['dataset_id'] = $dataset->id;
      
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
        $excludeColumns = ['uid', 'datasource_id', 'geom', 'geog' , 'image_path' , 'udatestart' , 'udateend'];

        $dataitems = $dataset->dataitems;
        $colheads = array();
        $extkeys = array();
        // !!!!!!!!!! actually also go through the headers and only put them in if at least one is not null.....

        // Fudge to convert object with properties to key value pairs
        if (!empty($dataitems) && isset($dataitems[0])) {
            $arr = json_decode(json_encode($dataitems[0]), true);
        } else {
            $arr = []; 
        }

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
        $headerValueForDisplay = ['id' => 'ghap_id', 'external_url' => 'linkback', 'dataset_id' => 'layer_id', 'recordtype_id' => 'record_type'];
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
                if ($cellValue !== "" && $col === 'recordtype_id') {
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
     * Calculates basic statistical data for a dataset.
     *
     * Compiles a series of basic statistical measures for the dataset
     * Such as the total number of places, the area of the convex hull encompassing all places,
     * the density of places, the centroid of all places, and the bounding box around all places. 
     *
     * @return array
     *   An array of statistical measures, each containing name, value, unit, and an explanation of the statistic.
     */
    public function getBasicStatistics()
    {

        $statistics = [];

        // Total Places
        $statistics[] = [
            'name' => 'Total Places',
            'value' => $this->dataitems()->count(),
            'unit' => null,
            'explanation' => 'The total number of places'
        ];

        // Area of the convex hull
        $areaResult = DB::select(DB::raw("
            SELECT ST_Area(ST_ConvexHull(ST_Collect(geog::geometry))) as area 
            FROM tlcmap.dataitem 
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $totalArea = $areaResult[0]->area ? $areaResult[0]->area * 10000 : null;
        $statistics[] = [
            'name' => 'Area',
            'value' => $totalArea ?? 'Not Available',
            'unit' => 'km<sup>2</sup>',
            'explanation' => 'The area of the convex hull of all places in the dataset'
        ];

        // Convex hull polygon
        $convexHullResult = DB::select(DB::raw("
            SELECT 
                ST_AsText(ST_ConvexHull(ST_Collect(geog::geometry))) AS convex_hull_line
            FROM 
                tlcmap.dataitem 
            WHERE 
                dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $statistics[] = [
            'name' => 'Convex Hull',
            'value' => $convexHullResult[0]->convex_hull_line,
            'explanation' => 'The area of the convex hull'
        ];

        // Density
        if ($totalArea  && $this->dataitems()->count()) {
            $statistics[] = [
                'name' => 'Density',
                'value' => $this->dataitems()->count() / max($totalArea, 1),
                'unit' => 'places/km<sup>2</sup>',
                'explanation' => 'The amount of places per square kilometer'
            ];
        }

        // Centroid
        $centroidResult = DB::select(DB::raw("
            SELECT ST_AsText( ST_Centroid(ST_Collect(geog::geometry))) as centroid 
            FROM tlcmap.dataitem
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $statistics[] = [
            'name' => 'Centroid',
            'value' => $centroidResult[0]->centroid ?? 'Not Available',
            'unit' => null,
            'explanation' => 'The midpoint between all places'
        ];

        // Bounding box
        $bboxResult = DB::select(DB::raw("
            SELECT ST_AsText(ST_Extent(geog::geometry)) as bbox 
            FROM tlcmap.dataitem 
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $statistics[] = [
            'name' => 'Bounding Box',
            'value' => $bboxResult[0]->bbox ?? 'Not Available',
            'unit' => null,
            'explanation' => 'Coordinates showing a box enclosing all places, using the coordinates that are furthest north, south, east and west.'
        ];

        // Calculate centroid coordinates
        $centroidCoords = DB::select(DB::raw("
            SELECT ST_Y(ST_Centroid(ST_Collect(geog::geometry))) as lat, 
                ST_X(ST_Centroid(ST_Collect(geog::geometry))) as lng 
            FROM tlcmap.dataitem 
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $centroidLat = $centroidCoords[0]->lat ?? 0;
        $centroidLng = $centroidCoords[0]->lng ?? 0;

        // Most central place
        $centralPlace = DB::select(DB::raw("
        SELECT id, title, latitude, longitude, uid , 
            ST_Distance(geog, ST_MakePoint(:centroidLng, :centroidLat)::geography) as distance 
        FROM tlcmap.dataitem
        WHERE dataset_id = :dataset_id
        ORDER BY distance ASC
        LIMIT 1
        "), ['dataset_id' => $this->id, 'centroidLng' => $centroidLng, 'centroidLat' => $centroidLat]);

        if (!empty($centralPlace)) {
            $placeUrl = config('app.views_root_url') . '/3d.html?load=' . (url('places/' . $centralPlace[0]->uid . '/json'));
        } else {
            $placeUrl = null;
        }

        $statistics[] = [
            'name' => 'Most Central Place',
            'value' => $centralPlace[0]->title ?? 'Not Available',
            'url' => $placeUrl,
            'unit' => null,
            'explanation' => 'The place that is closest to the centroid of all places'
        ];

        // Most distant place from center
        $distantPlace = DB::select(DB::raw("
        SELECT id, title, latitude, longitude, uid , 
            ST_Distance(geog, ST_MakePoint(:centroidLng, :centroidLat)::geography) as distance 
        FROM tlcmap.dataitem 
        WHERE dataset_id = :dataset_id
        ORDER BY distance DESC
        LIMIT 1
        "), ['dataset_id' => $this->id, 'centroidLng' => $centroidLng, 'centroidLat' => $centroidLat]);

        if (!empty($distantPlace)) {
            $placeUrl = config('app.views_root_url') . '/3d.html?load=' . (url('places/' . $distantPlace[0]->uid . '/json'));
        } else {
            $placeUrl = null;
        }

        $statistics[] = [
            'name' => 'Most Distant Place from center',
            'value' => $distantPlace[0]->title ?? 'Not Available',
            'url' => $placeUrl,
            'unit' => null,
            'explanation' => 'The place that is furthest from the centroid of all places'
        ];

        // Distribution
        // Fetch all places
        $places = DB::select(DB::raw("
        SELECT id, ST_Distance(geog, ST_MakePoint(:centroidLng, :centroidLat)::geography) as distanceToCentroid
            FROM tlcmap.dataitem
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id, 'centroidLng' => $centroidLng, 'centroidLat' => $centroidLat]);

        // Convert the result to an array of distances to centroid
        $distancesToCentroid = array_map(function ($place) {
            return (float) $place->distancetocentroid;
        }, $places);
        // Calculate average distance to centroid
        $averageDistanceToCentroid = array_sum($distancesToCentroid) / count($distancesToCentroid) / 1000;
        $ratio = $totalArea ? $averageDistanceToCentroid / $totalArea : "N/A";

        $statistics[] = [
            'name' => 'Distribution',
            'value' => [
                'Average Distance from Centroid' => $averageDistanceToCentroid,
                'Average Distance from Centroid / Area of Convex Hull' => $ratio,
            ],
            'unit' => 'kilometers',
            'explanation' => 'N/A'
        ];

        return $statistics;
    }

    /**
     * Generates a GeoJSON representation of basic statistics and spatial features for the dataset.
     *
     * @return string
     *   A string representation of the GeoJSON object including features and metadata for the dataset.
     */
    public function getBasicStatisticsJSON()
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
        $featureCollectionConfig->setInfoTitle( "Baisc Statistics: " .  $metadata['name'], $metadata['ghap_url']);
        $featureCollectionConfig->setInfoContent(GhapConfig::createDatasetInfoBlockContent($dataset));

        // Area of the convex hull: Show polygon
        $areaResult = DB::select(DB::raw("
            SELECT 
                ST_AsText(ST_ConvexHull(ST_Collect(geog::geometry))) AS convex_hull_line
            FROM 
                tlcmap.dataitem 
            WHERE 
                dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $polygons = $this->parseGeometryString($areaResult[0]->convex_hull_line);
        $linecoords = array();
        foreach ($polygons as $polygon) {
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => $polygon),
                'properties' => array('name' => 'Convex Hull', 'latitude' => $polygon[1], 'longitude' => $polygon[0]),
            );
            array_push($linecoords, [$polygon[0], $polygon[1]]);
        }
        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'LineString', 'coordinates' => $linecoords),
            'properties' => ['name' => 'Convex Hull Polygon'],
        );

        // Centroid : Show single point 
        $centroidResult = DB::select(DB::raw("
            SELECT ST_AsText( ST_Centroid(ST_Collect(geog::geometry))) as centroid 
            FROM tlcmap.dataitem
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $coordinates = $this->parseGeometryString($centroidResult[0]->centroid);
        foreach ($coordinates as $coordinate) {
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => $coordinate),
                'properties' => array('name' => 'Centroid', 'latitude' => $coordinate[1], 'longitude' => $coordinate[0]),
            );
        }

        // Bounding box: Show polygon
        $bboxResult = DB::select(DB::raw("
            SELECT ST_AsText(ST_Extent(geog::geometry)) as bbox 
            FROM tlcmap.dataitem 
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);
        $polygons = $this->parseGeometryString($bboxResult[0]->bbox);
        $linecoords = array();
        foreach ($polygons as $polygon) {
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => $polygon),
                'properties' => array('name' => 'Bounding Box', 'latitude' => $polygon[1], 'longitude' => $polygon[0]),
            );
            array_push($linecoords, [$polygon[0], $polygon[1]]);
        }
        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'LineString', 'coordinates' => $linecoords),
            'properties' => ['name' => 'Bounding Box Polygon'],
        );

        // Most central place : Show single point
        $centroidCoords = DB::select(DB::raw("
            SELECT ST_Y(ST_Centroid(ST_Collect(geog::geometry))) as lat, 
                ST_X(ST_Centroid(ST_Collect(geog::geometry))) as lng 
            FROM tlcmap.dataitem 
            WHERE dataset_id = :dataset_id
        "), ['dataset_id' => $this->id]);

        $centroidLat = $centroidCoords[0]->lat ?? 0;
        $centroidLng = $centroidCoords[0]->lng ?? 0;
        $centralPlace = DB::select(DB::raw("
        SELECT id, title, latitude, longitude, uid , 
            ST_Distance(geog, ST_MakePoint(:centroidLng, :centroidLat)::geography) as distance 
        FROM tlcmap.dataitem
        WHERE dataset_id = :dataset_id
        ORDER BY distance ASC
        LIMIT 1
        "), ['dataset_id' => $this->id, 'centroidLng' => $centroidLng, 'centroidLat' => $centroidLat]);
        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$centralPlace[0]->longitude, $centralPlace[0]->latitude]),
            'properties' => array('name' => 'Most central place', 'latitude' => $centralPlace[0]->latitude, 'longitude' => $centralPlace[0]->longitude),
        );

        $allfeatures = array(
            'type' => 'FeatureCollection',
            'metadata' => $metadata,
            'features' => $features,
            'display' => $featureCollectionConfig->toArray(),
        );

        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }

    /**
     * Calculates advanced statistical data for a dataset.
     *
     * Computes distances between all pairs of places within the dataset and derives
     * several statistical measures from these distances, such as minimum, maximum, average, and median distances.
     * It also identifies the most isolated place within the dataset based on the minimum distances.
     *
     * @return array
     *   An array of advanced statistical measures
     */
    public function getAdvancedStatistics()
    {
        $statistics = [];

        // Distances between places 
        $distances = DB::select(DB::raw("
            SELECT a.id as place_a_id, b.id as place_b_id, ST_Distance(a.geog, b.geog) as distance 
            FROM tlcmap.dataitem a, tlcmap.dataitem b 
            WHERE a.dataset_id = :dataset_id AND b.dataset_id = :dataset_id AND a.id < b.id
        "), ['dataset_id' => $this->id]);

        $distanceValues = array_column($distances, 'distance');
        $nearestNeighborDistances = [];

        foreach ($distances as $distance) {
            $placeA = $distance->place_a_id;
            $placeB = $distance->place_b_id;
            $dist = $distance->distance;

            if (!isset($nearestNeighborDistances[$placeA]) || $dist < $nearestNeighborDistances[$placeA]) {
                $nearestNeighborDistances[$placeA] = $dist;
            }

            if (!isset($nearestNeighborDistances[$placeB]) || $dist < $nearestNeighborDistances[$placeB]) {
                $nearestNeighborDistances[$placeB] = $dist;
            }
        }

        // Calculate statistics
        $minDistance = min($distanceValues) / 1000;
        $maxDistance = max($distanceValues) / 1000;
        $averageDistance = array_sum($distanceValues) / count($distanceValues) / 1000;
        $medianDistance = GeneralFunctions::getMedian($distanceValues) / 1000;
        $stdDevDistance = GeneralFunctions::getStandardDeviation($distanceValues) / 1000;
        $averageMinDistance = array_sum($nearestNeighborDistances) / count($nearestNeighborDistances) / 1000;
        $medianMinDistance = GeneralFunctions::getMedian($nearestNeighborDistances) / 1000;
        $stdDevMinDistance = GeneralFunctions::getStandardDeviation($nearestNeighborDistances) / 1000;

        $statistics[] = [
            'name' => 'Distance Between Places',
            'value' => [
                'Min distance between 2 places' => $minDistance,
                'Max distance between 2 places' => $maxDistance,
                'Average distance between places' => $averageDistance,
                'Median distance between places' => $medianDistance,
                'Standard Deviation of distance between places' => $stdDevDistance,
                'Average Min distance between neighbouring places' => $averageMinDistance,
                'Median Min distance between neighbouring places' => $medianMinDistance,
                'Standard Deviation of Min distance between neighbouring places' => $stdDevMinDistance
            ],
            'unit' => 'kilometers',
            'explanation' => 'Minimum, Maximum and typical distances between places in kilometers. This can also indicate how closely grouped or evenly scattered places are. The min distance is important to understand how ‘close’ places tend to be to each other. See also ‘Distribution’'
        ];


        //Most distance place from any other place
        // Now find the place with the largest minimum distance to any other place
        $maxMinDistance = max($nearestNeighborDistances);
        $mostIsolatedPlaceId = array_search($maxMinDistance, $nearestNeighborDistances);

        // Fetch details for the most isolated place
        $mostIsolatedPlace = DB::select(DB::raw("
            SELECT id, title, latitude, longitude , uid
            FROM tlcmap.dataitem 
            WHERE id = :id
        "), ['id' => $mostIsolatedPlaceId]);

        if (!empty($mostIsolatedPlace)) {
            $placeUrl = config('app.views_root_url') . '/3d.html?load=' . (url('places/' . $mostIsolatedPlace[0]->uid . '/json'));
        } else {
            $placeUrl = null;
        }

        $statistics[] = [
            'name' => 'Most Distant Place From Any Other Place',
            'url' => $placeUrl,
            'value' => [
                'Title' => $mostIsolatedPlace[0]->title,
                'Coordinates' => "{$mostIsolatedPlace[0]->latitude}, {$mostIsolatedPlace[0]->longitude}",
            ],
            'unit' => null,
            'explanation' => 'The place most isolated from others. This is different to distance to the centre, and different to the max distance between any two places. It is the largest of all minimum distances.'
        ];
        return $statistics;
    }

    /**
     * Performs DBSCAN clustering on dataset items based on geographical proximity.
     *
     * This method applies the DBSCAN (Density-Based Spatial Clustering of Applications with Noise) algorithm
     * to the items within the dataset, grouping them into clusters based on their spatial location.
     *
     * @param float $distance The maximum distance between two points for one to be considered as in the neighborhood of the other.
     * @param int $minPoints The minimum number of points required to form a dense region (cluster).
     * @return array An array of clusters, each containing the items that belong to that cluster.
     */
    public function getClusterAnalysisDBScan($distance, $minPoints)
    {
        $distance = $distance / 100 ;        
        // Perform the DBSCAN clustering query using Eloquent
        $dataitems = Dataitem::selectRaw(
            "*, ST_ClusterDBSCAN(geom, eps := ?, minpoints := ?) OVER() AS cluster_id", 
            [$distance, $minPoints]
        )->where('dataset_id', $this->id)
         ->get();

        return [
            "data" => $this->processClusterData($dataitems),
            "name" => $this->name
        ];
    }

    /**
     * Generates a JSON representation of the DBSCAN clustering results for visualization.
     *
     * @return string A JSON string representing the clustered data, ready for web visualization.
     */
    public function getClusterAnalysisDBScanJSON()
    {
        $dataset = $this;

        $distance = $_GET["distance"] / 100;
        $minPoints = $_GET["minPoints"];

        $dataitems = Dataitem::selectRaw(
            "*, ST_ClusterDBSCAN(geom, eps := ?, minpoints := ?) OVER() AS cluster_id", 
            [$distance, $minPoints]
        )->where('dataset_id', $this->id)
         ->get();

        $groupedClusters = $this->processClusterData($dataitems);

        $data = [];
        // Set collection config.
        $collectionConfig = new CollectionConfig();
        $collectionConfig->setInfoTitle('DBSCAN Clustering of layer ' . $this->name);
        $data['display'] = $collectionConfig->toArray();
        $data['datasets'] = [];

        foreach ($groupedClusters as $clusterId => $cluster) {
            $features = [];
            foreach ($cluster as $place) {

                $featureConfig = new FeatureConfig();
                // Set footer links.
                if($place['ghap_id']){
                    $featureConfig->addLink("TLCMap Record: {$place['ghap_id']}", url('places/' . $place['ghap_id']));
                }
                if($place['layer_id']){
                    $featureConfig->addLink('TLCMap Layer', url('layers/' . $place['layer_id']));
                }

                $feature = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$place['longitude'], $place['latitude']]
                    ],
                    'properties' => $place,
                    'display' => $featureConfig->toArray(),
                ];
                $features[] = $feature;
            }

            $metadata = array(
                'layerid' => $dataset->id,
                'name' => $dataset->name,
                'description' => $dataset->description,
                'warning' => $dataset->warning,
                'ghap_url' => $dataset->public ? url("publicdatasets/{$dataset->id}") : url("myprofile/mydatasets/{$dataset->id}"),
                'linkback' => $dataset->linkback
            );

            $featureCollectionConfig = new FeatureCollectionConfig();
            $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
            $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());
            $featureCollectionConfig->setInfoTitle($metadata['name'], $metadata['ghap_url']);
            $displayProperty = $featureCollectionConfig->toArray();
            $displayProperty['listPane']['showColor'] = true;

            $data['datasets'][] = [
                'name' => 'Cluster ' . ($clusterId + 1),
                'type' => 'FeatureCollection',
                'features' => $features,
                'display' => $displayProperty,
            ];
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Performs KMeans clustering on the dataset's locations.
     *
     * This function clusters locations within a dataset into a specified number of clusters using the KMeans algorithm.
     * Optionally, a maximum radius for clusters can be specified.
     *
     * @param int $numberOfClusters The number of clusters to generate.
     * @param float|null $withinRadius Optional. The maximum radius (in kilometers) for a cluster. Defaults to null.
     * @return array An associative array where keys are cluster IDs and values are arrays of location data belonging to that cluster.
     */
    public function getClusterAnalysisKmeans($numberOfClusters, $withinRadius = null)
    {
        $withinRadius = $withinRadius ?? null ;

        // Perform initial KMeans clustering
        $dataitems = Dataitem::selectRaw("
             *, ST_ClusterKMeans(geom::geometry, ?) OVER() AS cluster_id",
            [$numberOfClusters])
        ->where('dataset_id', $this->id)
        ->get();

        $clusterResults = $this->processClusterData($dataitems);

        $clusterGeoms = [];
        foreach ($dataitems as $dataitem) {
            $clusterId = $dataitem->cluster_id;
            if ($clusterId !== null) {
                $clusterGeoms[$clusterId][] = "ST_GeomFromText('POINT(" . $dataitem->longitude . " " . $dataitem->latitude . ")')";
            }
        }

        if (is_null($withinRadius)) {
            return [
                "data" => $clusterResults,
                "name" => $this->name
            ];
        }

        $filteredClusters = [];

        foreach ($clusterGeoms as $clusterId => $geoms) {
            $geomCollection = implode(',', $geoms);
            $centroidResult = DB::select(DB::raw("
                SELECT ST_AsText(ST_Centroid(ST_Collect(array[$geomCollection]))) AS centroid
            "));
            $exceedsMaxRadius = false;

            if($centroidResult[0]->centroid){
                $centroidLat = $this->parseGeometryString($centroidResult[0]->centroid)[0][1];
                $centroidLng = $this->parseGeometryString($centroidResult[0]->centroid)[0][0];

                foreach ($clusterResults[$clusterId] as $place) {
                    $distance = GeneralFunctions::getDistance($place['latitude'] , $place['longitude'], $centroidLat, $centroidLng);
                    if ($distance > $withinRadius) {
                        $exceedsMaxRadius = true;
                        break;
                    }
                }
            }else{
                //Error in centroid calculation
                $exceedsMaxRadius = true;
            }

            if (!$exceedsMaxRadius) {
                $filteredClusters[$clusterId] = $clusterResults[$clusterId];
            }
           
        }

        // Restart cluster number
        $cluster_id = 1;
        foreach ($filteredClusters as &$filteredCluster) {  
            foreach ($filteredCluster as &$place) {  
                $place['Cluster_Id'] = $cluster_id;
            }
            $cluster_id++;
        }

        return [
            "data" => array_values($filteredClusters),
            "name" => $this->name
        ];
    
    }

    /**
     * Generates a GeoJSON representation of KMeans clustering results for visualization.
     *
     * @return string JSON string representing the clustered locations in GeoJSON format.
     */
    public function getClusterAnalysisKmeansJSON()
    {
        $dataset = $this;

        $numClusters = $_GET["numClusters"];
        $withinRadius = $_GET["withinRadius"];

        // Perform initial KMeans clustering
        $dataitems = Dataitem::selectRaw("
             *, ST_ClusterKMeans(geom::geometry, ?) OVER() AS cluster_id",
            [$numClusters])
        ->where('dataset_id', $this->id)
        ->get();

        $clusterResults = $this->processClusterData($dataitems);

        $clusterGeoms = [];
        foreach ($dataitems as $dataitem) {
            $clusterId = $dataitem->cluster_id;
            if ($clusterId !== null) {
                $clusterGeoms[$clusterId][] = "ST_GeomFromText('POINT(" . $dataitem->longitude . " " . $dataitem->latitude . ")')";
            }
        }

        if (  $withinRadius != null && $withinRadius != 'null' ) {
            $filteredClusters = [];
            foreach ($clusterGeoms as $clusterId => $geoms) {
                $geomCollection = implode(',', $geoms);
                $centroidResult = DB::select(DB::raw("
                    SELECT ST_AsText(ST_Centroid(ST_Collect(array[$geomCollection]))) AS centroid
                "));
                $exceedsMaxRadius = false;
    
                if($centroidResult[0]->centroid){
                    $centroidLat = $this->parseGeometryString($centroidResult[0]->centroid)[0][1];
                    $centroidLng = $this->parseGeometryString($centroidResult[0]->centroid)[0][0];
    
                    foreach ($clusterResults[$clusterId] as $place) {
                        $distance = GeneralFunctions::getDistance($place['latitude'] , $place['longitude'], $centroidLat, $centroidLng);
                        if ($distance > $withinRadius) {
                            $exceedsMaxRadius = true;
                            break;
                        }
                    }
                }else{
                    //Error in centroid calculation
                    $exceedsMaxRadius = true;
                }
    
                if (!$exceedsMaxRadius) {
                    $filteredClusters[$clusterId] = $clusterResults[$clusterId];
                }
               
            }

            // Restart cluster number
            $cluster_id = 1;
            foreach ($filteredClusters as &$filteredCluster) {  
                foreach ($filteredCluster as &$place) {  
                    $place['Cluster_Id'] = $cluster_id;
                }
                $cluster_id++;
            }
            $clusterResults = $filteredClusters;
        }

        $data = [];
        // Set collection config.
        $collectionConfig = new CollectionConfig();
        $collectionConfig->setInfoTitle('Kmeans Clustering of layer ' . $this->name);
        $data['display'] = $collectionConfig->toArray();
        $data['datasets'] = [];

        foreach ($clusterResults as $cluster) {
            $features = [];
            $clusterId = null;
            foreach ($cluster as $place) {

                $featureConfig = new FeatureConfig();
                // Set footer links.
                if($place['ghap_id']){
                    $featureConfig->addLink("TLCMap Record: {$place['ghap_id']}", url('places/' . $place['ghap_id']));
                }
                if($place['layer_id']){
                    $featureConfig->addLink('TLCMap Layer', url('layers/' . $place['layer_id']));
                }

                $clusterId = $place['Cluster_Id'];

                $feature = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$place['longitude'], $place['latitude']]
                    ],
                    'properties' => $place,
                    'display' => $featureConfig->toArray(),
                ];
                $features[] = $feature;
            }

            $metadata = array(
                'layerid' => $dataset->id,
                'name' => $dataset->name,
                'description' => $dataset->description,
                'warning' => $dataset->warning,
                'ghap_url' => $dataset->public ? url("publicdatasets/{$dataset->id}") : url("myprofile/mydatasets/{$dataset->id}"),
                'linkback' => $dataset->linkback
            );

            $featureCollectionConfig = new FeatureCollectionConfig();
            $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
            $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());
            $featureCollectionConfig->setInfoTitle($metadata['name'], $metadata['ghap_url']);
            $displayProperty = $featureCollectionConfig->toArray();
            $displayProperty['listPane']['showColor'] = true;

            $data['datasets'][] = [
                'name' => 'Cluster ' . $clusterId,
                'type' => 'FeatureCollection',
                'features' => $features,
                'display' => $displayProperty,
            ];
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Performs temporal clustering of data items based on the specified time interval.
     *
     * @param float $totalInterval The temporal interval (in years) used to define the clusters. Data items within this interval from each other are grouped into the same cluster.
     * @return array An associative array containing the count of dropped records (without a valid date) and the clusters formed.
     */
    public function getTemporalClustering($totalInterval)
    {
        $datasetId = $this->id;

        // Calculate the dropped records 
        $droppedRecordsCount = DB::table('tlcmap.dataitem')
            ->where('dataset_id', $datasetId)
            ->where(function ($query) {
                $query->whereNull('datestart');
            })
            ->count();

        $dataitems = Dataitem::where('dataset_id', $datasetId)
            ->whereNotNull('datestart')
            ->get();

        // Preprocess dates
        $processedDataitems = $dataitems->map(function ($dataitem) {
            $dataitem->geom_date = $this->proprecssDataitemDate($dataitem->datestart);
            return $dataitem;
        })->filter(function ($dataitem) {
            return $dataitem->geom_date !== null;
        })->sortBy('geom_date');

        $previousDate = null;
        $clusterId = 0;

        // Temporal clustering. Add cluster id to dataitem
        foreach ($processedDataitems as &$dataitem) {
            if ($previousDate !== null) {
                $dateDiff = $dataitem->geom_date - $previousDate;
                if ($dateDiff > $totalInterval) {
                    $clusterId++;                 
                } 
            }
            $dataitem['cluster_id'] = $clusterId;
            $previousDate = $dataitem->geom_date;
        }

        return [
            'droppedRecordsCount' => $droppedRecordsCount,
            'clusters' => $this->processClusterData($processedDataitems),
            'name' => $this->name
        ];
    }

    /**
     * Generates a JSON representation of temporal clustering for visualization.
     *
     * @return string A JSON string representing the clustered data items in GeoJSON format.
     */
    public function getTemporalClusteringJSON()
    {
        $dataset = $this;
        $datasetId = $this->id;
        $yearsInterval =  $_GET["year"] ? $_GET["year"] : 0;
        $daysInterval = $_GET["day"] ? $_GET["day"] : 0;
        $totalInterval = $yearsInterval  + $daysInterval / 366;

        $dataitems = Dataitem::where('dataset_id', $datasetId)
            ->whereNotNull('datestart')
            ->get();

        // Preprocess dates
        $processedDataitems = $dataitems->map(function ($dataitem) {
            $dataitem->geom_date = $this->proprecssDataitemDate($dataitem->datestart);
            return $dataitem;
        })->filter(function ($dataitem) {
            return $dataitem->geom_date !== null;
        })->sortBy('geom_date');

        $previousDate = null;
        $clusterId = 0;

        // Temporal clustering. Add cluster id to dataitem
        foreach ($processedDataitems as &$dataitem) {
            if ($previousDate !== null) {
                $dateDiff = $dataitem->geom_date - $previousDate;
                if ($dateDiff > $totalInterval) {
                    $clusterId++;                 
                } 
            }
            $dataitem['cluster_id'] = $clusterId;
            $previousDate = $dataitem->geom_date;
        }

        $clusters = $this->processClusterData($processedDataitems);

        $data = [];
        // Set collection config.
        $collectionConfig = new CollectionConfig();
        $collectionConfig->setInfoTitle('Temporal Clustering of layer ' . $this->name);
        $data['display'] = $collectionConfig->toArray();
        $data['datasets'] = [];

        foreach ($clusters as $clusterId => $cluster) {
            $features = [];
            foreach ($cluster as $place) {

                $featureConfig = new FeatureConfig();
                // Set footer links.
                if($place['ghap_id']){
                    $featureConfig->addLink("TLCMap Record: {$place['ghap_id']}", url('places/' . $place['ghap_id']));
                }
                if($place['layer_id']){
                    $featureConfig->addLink('TLCMap Layer', url('layers/' . $place['layer_id']));
                }

                $feature = [
                    'type' => 'Feature',
                    'geometry' => [
                        'type' => 'Point',
                        'coordinates' => [$place['longitude'], $place['latitude']]
                    ],
                    'properties' => $place,
                    'display' => $featureConfig->toArray(),
                ];
                $features[] = $feature;
            }

            $metadata = array(
                'layerid' => $dataset->id,
                'name' => $dataset->name,
                'description' => $dataset->description,
                'warning' => $dataset->warning,
                'ghap_url' => $dataset->public ? url("publicdatasets/{$dataset->id}") : url("myprofile/mydatasets/{$dataset->id}"),
                'linkback' => $dataset->linkback
            );

            $featureCollectionConfig = new FeatureCollectionConfig();
            $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
            $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());
            $featureCollectionConfig->setInfoTitle($metadata['name'], $metadata['ghap_url']);
            $displayProperty = $featureCollectionConfig->toArray();
            $displayProperty['listPane']['showColor'] = true;

            $data['datasets'][] = [
                'name' => 'Cluster ' . ($clusterId + 1),
                'type' => 'FeatureCollection',
                'features' => $features,
                'display' => $displayProperty,
            ];
        }

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Processes an array of dataitem Model for clustering, performing key renames, exclusions, and data adding.
     * Used for csv and kml download
     *  1. Exclude specific keys
     *  2. Change name of some keys for better display
     *  3. Remove column with all null values
     *  4. Add extended data to the dataitem
     *  5. Ensure Cluster_Id is the first key and ghap_id is the second key
     *  6. Start cluster_id from 1 instead of 0
     *  7. Special handling for recordtype, store type instead of id
     * 
     * @param array $dataitems The array of data items to process, dataitem inlcude field called cluster_id
     * @return array An array of clusters with processed data items, each enhanced and ready for csv and kml export.
     */
    private function processClusterData($dataitems)
    {
        $excludeColumns = ['id', 'datasource_id', 'geom', 'geog', 'geom_date' ,  'image_path', 'dataset_order' , 'extended_data' , 'kml_style_url'];
        $headerValueForDisplay = [
            'uid' => 'ghap_id',
            'external_url' => 'linkback',
            'dataset_id' => 'layer_id',
            'recordtype_id' => 'record_type',
            'cluster_id' => 'Cluster_Id'
        ];

        // Initialize an array to track non-null occurrences of keys
        $nonnullKeyTracker = [];
        foreach ($dataitems as $dataitem) {
            foreach ($dataitem->toArray() as $key => $value) {
                if ($value !== null) {
                    $nonnullKeyTracker[$key] = true;
                }
            }
        }
    
        $clusterResults = [];
    
        foreach ($dataitems as $dataitem) {
            $clusterId = $dataitem->cluster_id;
    
            if ($clusterId !== null) { 
                $dataArray = $dataitem->toArray();
                $extendedData = $dataitem->getExtendedData();
    
                // Rename keys as per headerValueForDisplay and remove excluded columns and columns with all null value
                foreach ($dataArray as $key => $value) {
                    if (in_array($key, $excludeColumns) || !array_key_exists($key, $nonnullKeyTracker)) {
                        unset($dataArray[$key]);
                    } 
                    
                    if (array_key_exists($key, $headerValueForDisplay)) {
                        $dataArray[$headerValueForDisplay[$key]] = $value;
                        unset($dataArray[$key]);
                    }  
                }

                // Special handling for recordtype, store type instead of id
                $dataArray['record_type'] = RecordType::getTypeById($dataArray['record_type']);
                // Start cluster_id from 1 instead of 0
                $dataArray['Cluster_Id'] += 1;
        
                // Add extended data
                if (!empty($extendedData)) {
                    foreach ($extendedData as $key => $value) {
                        if (!array_key_exists($key, $dataArray)) {
                            $dataArray[$key] = $value;
                        }
                    }
                }
    
                // Ensure Cluster_Id is the first key and ghap_id is the second key
                $res = [
                    'Cluster_Id' => $dataArray['Cluster_Id'],
                    'ghap_id' => $dataArray['ghap_id']
                ];
                unset($dataArray['Cluster_Id'], $dataArray['ghap_id']);
                $res += $dataArray;
    
                $clusterResults[$clusterId][] = $res;
            }
        }
    
        return $clusterResults;
    }
    
    /**
     * Converts various date string formats to a float representation capturing the year and fractional part of the year.
     *
     * @param string $dateString The date string to be converted.
     * @return float|null The float representation of the date as year plus a fractional component, or null if the date string is not in a supported format.
     */
    public function proprecssDataitemDate($dateString)
    {
        if (empty($dateString)) {
            return null; // empty strings
        }

        // Year only or year with invalid month/day
        if (preg_match('/^(\d{1,4})-00-00$/', $dateString, $matches)) {
            return (float)$matches[1];
        }

        // Invalid day/month with valid year
        if (preg_match('/00\/00\/(\d{1,4})$/', $dateString, $matches)) {
            return (float)$matches[1];
        }

        // Year and month only
        if (preg_match('/^(\d{4})-(0[1-9]|1[012])$/', $dateString, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $dayOfYear = round(($month - 1) * 30.44); // Approximation
            return $year + ($dayOfYear / 366);
        }

        // Full date in ISO 8601 or similar format
        if (preg_match('/^(\d{4})-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])$/', $dateString, $matches)) {
            $year = (int)$matches[1];
            $month = (int)$matches[2];
            $day = (int)$matches[3];
            $dayOfYear = round(($month - 1) * 30.44 + $day); // Approximation
            return $year + ($dayOfYear / 366);
        }

        // DD/MM/YYYY format
        if (preg_match('/^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[012])\/(\d{4})$/', $dateString, $matches)) {
            $year = (int)$matches[3];
            $month = (int)$matches[2];
            $day = (int)$matches[1];
            $dayOfYear = round(($month - 1) * 30.44 + $day); // Approximation
            return $year + ($dayOfYear / 366);
        }

        return null;
    }

    /**
     * Performs a closeness analysis between two datasets, calculating various distance metrics.
     *
     * This function calculates the closeness of points in the current dataset (A) to points in another dataset (B),
     * identified by `$targetDatasetId`. It computes the area of the convex hull for dataset A, and for each point in A,
     * it finds the minimum distance to any point in B. Statistical metrics such as average, minimum, maximum, and median
     * minimum distances are calculated to summarize the closeness. The distances are also normalized by the area of the
     * convex hull to provide additional insights.
     *
     * @param int $targetDatasetId The ID of the target dataset B to compare against.
     * @return array An associative array containing distance metrics and their area-normalized counterparts.
     */
    public function getClosenessAnalysis($targetDatasetId)
    {
        $sourceDatasetId = $this->id;

        // Calculate the area of the convex hull of A
        $convexHullArea = DB::table('tlcmap.dataitem')
            ->select(DB::raw('ST_Area(ST_ConvexHull(ST_Collect(geog::geometry))) as area'))
            ->where('dataset_id', $sourceDatasetId)
            ->value('area');

        // Calculate distances from each point in A to the closest point in B, including minimum and maximum distances
        $minDistanceRecords = DB::table('tlcmap.dataitem as a')
        ->join('tlcmap.dataitem as b', function ($join) use ($sourceDatasetId, $targetDatasetId) {
            $join->on(DB::raw('1'), '=', DB::raw('1')) // Cross join
                ->where('a.dataset_id', '=', $sourceDatasetId)
                ->where('b.dataset_id', '=', $targetDatasetId);
        })
        ->select(DB::raw('a.id as source_id, MIN(ST_Distance(a.geog, b.geog)) as min_distance'))
        ->groupBy('a.id')
        ->get();

        $minDistances = $minDistanceRecords->pluck('min_distance');

        $averageMinDistance = $minDistances->average() / 1000;
        $minMinDistance = $minDistances->min() / 1000;
        $maxMinDistance = $minDistances->max() / 1000;
        $medianMinDistance = $minDistances->median() / 1000;

        $maxDistance = DB::table('tlcmap.dataitem as a')
        ->join('tlcmap.dataitem as b', function ($join) use ($sourceDatasetId, $targetDatasetId) {
            $join->on(DB::raw('1'), '=', DB::raw('1'))
                ->where('a.dataset_id', '=', $sourceDatasetId)
                ->where('b.dataset_id', '=', $targetDatasetId);
        })
        ->max(DB::raw('ST_Distance(a.geog, b.geog)')) / 1000;

        $res = [
            'Max Distance' => $maxDistance,
            'Average Min Distance' => $averageMinDistance,
            'Min Min Distance' => $minMinDistance,
            'Max Min Distance' => $maxMinDistance,
            'Median Min Distance' => $medianMinDistance,
            'Average Min Distance / Area' => $averageMinDistance / ($convexHullArea * 10000),
            'Min Min Distance / Area' => $minMinDistance / ($convexHullArea * 10000),
            'Max Min Distance / Area' => $maxMinDistance / ($convexHullArea * 10000),
            'Median Min Distance / Area' => $medianMinDistance / ($convexHullArea * 10000),
        ];

        return [
            'data' => $res,
            'name' => $this->name
        ];
    }

    /**
     * Generates a JSON representation of the closeness analysis results for visualization.
     *
     * @return string A JSON string representing the closeness analysis results, ready for web visualization.
     */
    public function getClosenessAnalysisJSON()
    {
        $sourceDatasetId = $this->id;
        $targetDatasetId = $_GET["targetLayer"];

        $dataset = $this;
        $features = array();

        $distanceRecords = DB::table('tlcmap.dataitem as a')
            ->join('tlcmap.dataitem as b', function ($join) use ($sourceDatasetId, $targetDatasetId) {
                $join->on(DB::raw('1'), '=', DB::raw('1')) // Cross join
                    ->where('a.dataset_id', '=', $sourceDatasetId)
                    ->where('b.dataset_id', '=', $targetDatasetId);
            })
            ->select(DB::raw('a.title as source_title, a.longitude as source_longitude, a.latitude as source_latitude ,  b.title as target_title, b.longitude as target_longitude, b.latitude as target_latitude , ST_Distance(a.geog, b.geog) as distance'))
            ->orderBy('distance')
            ->get();
        $distances = $distanceRecords->pluck('distance');
        $maxDistance = $distances->max() / 1000;
        $maxDistanceRecord = $distanceRecords->last();

        $minDistanceRecords = DB::table('tlcmap.dataitem as a')
            ->join('tlcmap.dataitem as b', function ($join) use ($sourceDatasetId, $targetDatasetId) {
                $join->on(DB::raw('1'), '=', DB::raw('1')) // Cross join
                    ->where('a.dataset_id', '=', $sourceDatasetId)
                    ->where('b.dataset_id', '=', $targetDatasetId);
            })
            ->select(DB::raw('a.id as source_id, MIN(ST_Distance(a.geog, b.geog)) as min_distance'))
            ->groupBy('a.id')
            ->get();
        $minDistances = $minDistanceRecords->pluck('min_distance');
        $minMinDistance = $minDistances->min();
        $maxMinDistance = $minDistances->max();

        $minMinDistanceRecord = $distanceRecords->filter(function ($record) use ($minMinDistance) {
            return $record->distance == $minMinDistance;
        })->first();

        $maxMinDistanceRecord = $distanceRecords->filter(function ($record) use ($maxMinDistance) {
            return $record->distance == $maxMinDistance;
        })->first();

    
        // Set the feature collection config.
        $featureCollectionConfig = new FeatureCollectionConfig();
        $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
        $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());
        $featureCollectionConfig->setInfoTitle( 'Closeness Analysis: ' . $dataset->name, $dataset->public ? url("publicdatasets/{$dataset->id}") : url("myprofile/mydatasets/{$dataset->id}"));
        $featureCollectionConfig->setInfoContent(GhapConfig::createDatasetInfoBlockContent($dataset));

        //Add the records to the features array
        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$minMinDistanceRecord->source_longitude, $minMinDistanceRecord->source_latitude]),
            'properties' => array('name' => $minMinDistanceRecord->source_title, 'latitude' => $minMinDistanceRecord->source_latitude, 'longitude' => $minMinDistanceRecord->source_longitude),
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$minMinDistanceRecord->target_longitude, $minMinDistanceRecord->target_latitude]),
            'properties' => array('name' => $minMinDistanceRecord->target_title, 'latitude' => $minMinDistanceRecord->target_latitude, 'longitude' => $minMinDistanceRecord->target_longitude),
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'LineString', 'coordinates' => [[$minMinDistanceRecord->source_longitude, $minMinDistanceRecord->source_latitude], [$minMinDistanceRecord->target_longitude, $minMinDistanceRecord->target_latitude]]),
            'properties' => ['name' => 'Shortest minimum Line' , 'distance' => $minMinDistance / 1000 . ' km'],
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$maxMinDistanceRecord->source_longitude, $maxMinDistanceRecord->source_latitude]),
            'properties' => array('name' => $maxMinDistanceRecord->source_title, 'latitude' => $maxMinDistanceRecord->source_latitude, 'longitude' => $maxMinDistanceRecord->source_longitude),
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$maxMinDistanceRecord->target_longitude, $maxMinDistanceRecord->target_latitude]),
            'properties' => array('name' => $maxMinDistanceRecord->target_title, 'latitude' => $maxMinDistanceRecord->target_latitude, 'longitude' => $maxMinDistanceRecord->target_longitude),
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'LineString', 'coordinates' => [[$maxMinDistanceRecord->source_longitude, $maxMinDistanceRecord->source_latitude], [$maxMinDistanceRecord->target_longitude, $maxMinDistanceRecord->target_latitude]]),
            'properties' => ['name' => 'Longest minimum Line' , 'distance' => $maxMinDistance / 1000 . ' km'],
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$maxDistanceRecord->source_longitude, $maxDistanceRecord->source_latitude]),
            'properties' => array('name' => $maxDistanceRecord->source_title, 'latitude' => $maxDistanceRecord->source_latitude, 'longitude' => $maxDistanceRecord->source_longitude),
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'Point', 'coordinates' => [$maxDistanceRecord->target_longitude, $maxDistanceRecord->target_latitude]),
            'properties' => array('name' => $maxDistanceRecord->target_title, 'latitude' => $maxDistanceRecord->target_latitude, 'longitude' => $maxDistanceRecord->target_longitude),
        );

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array('type' => 'LineString', 'coordinates' => [[$maxDistanceRecord->source_longitude, $maxDistanceRecord->source_latitude], [$maxDistanceRecord->target_longitude, $maxDistanceRecord->target_latitude]]),
            'properties' => ['name' => 'Max distance line' , 'distance' => $maxDistance . ' km'],
        );

        $allfeatures = array(
            'type' => 'FeatureCollection',
            'features' => $features,
            'display' => $featureCollectionConfig->toArray(),
        );

        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }


    /**
     * Parses a geometry string (POINT or POLYGON) and returns an array of coordinates.
     *
     * This function supports both POINT and POLYGON geometries, extracting and returning coordinates in a structured array.
     * 
     * @param string $geometryString The geometry string to parse.
     * @return array|null An array of coordinates, or null if the input format is unsupported.
     */
    protected function parseGeometryString($geometryString)
    {

        if (strpos($geometryString, "POLYGON") === 0) {
            $geometryString = trim($geometryString, "POLYGON()");
            $points = explode(",", $geometryString);
        } elseif (strpos($geometryString, "POINT") === 0) {
            $geometryString = trim($geometryString, "POINT()");
            $points = [$geometryString];
        } else {
            return null;
        }

        $coordinates = [];
        foreach ($points as $point) {
            $parts = explode(" ", trim($point));
            if (count($parts) === 2) {
                $coordinates[] = [$parts[0], $parts[1]];
            }
        }

        return $coordinates;
    }

}
