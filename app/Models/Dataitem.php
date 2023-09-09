<?php

namespace TLCMap\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use function foo\func;
use TLCMap\ViewConfig\FeatureCollectionConfig;
use TLCMap\ViewConfig\GhapConfig;
use TLCMap\ViewConfig\FeatureConfig;
use Illuminate\Support\Facades\URL;
use DOMDocument;

class Dataitem extends Model
{
    protected $table = "tlcmap.dataitem";
    public $timestamps = true;
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * This fillable property is important for the bulk dataitem import from the files. These fillable fields will be
     * matched and used during the import.
     *
     * @var array
     */
    protected $fillable = [
        'id', 'dataset_id', 'recordtype_id', 'title', 'description', 'latitude', 'longitude',
        'datestart', 'dateend', 'state', 'feature_term', 'lga', 'source', 'external_url',
        'extended_data', 'kml_style_url', 'placename', 'original_id', 'parish'
    ];

    /**
     * Defines a dataset relationship
     * 1 dataitem belongs to 1 dataset
     */
    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    public function recordtype()
    {
        return $this->belongsTo(RecordType::class, 'recordtype_id');
    }

    /**
     * Datasource which the data item belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function datasource()
    {
        return $this->belongsTo(Datasource::class, 'datasource_id');
    }

    /**
     * Get the start date.
     *
     * Accessor to normalise the date string in format yyyy-mm-dd.
     *
     * @param string $value
     * @return string
     */
    public function getDatestartAttribute($value)
    {
        if (preg_match('/^\d+-\d+-\d+$/', $value)) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    /**
     * Set the start date.
     *
     * Mutator to normalise the date string in format yyyy-mm-dd
     *
     * @param string $value
     * @return void
     */
    public function setDatestartAttribute($value)
    {
        if (preg_match('/^\d+-\d+-\d+$/', $value)) {
            $this->attributes['datestart'] = Carbon::parse($value)->format('Y-m-d');
        } else {
            $this->attributes['datestart'] = $value;
        }
    }

    /**
     * Get the end date.
     *
     * Accessor to normalise the date string in format yyyy-mm-dd.
     *
     * @param string $value
     * @return string
     */
    public function getDateendAttribute($value)
    {
        if (preg_match('/^\d+-\d+-\d+$/', $value)) {
            return Carbon::parse($value)->format('Y-m-d');
        }
        return $value;
    }

    /**
     * Set the end date.
     *
     * Mutator to normalise the date string in format yyyy-mm-dd
     *
     * @param string $value
     * @return void
     */
    public function setDateendAttribute($value)
    {
        if (preg_match('/^\d+-\d+-\d+$/', $value)) {
            $this->attributes['dateend'] = Carbon::parse($value)->format('Y-m-d');
        } else {
            $this->attributes['dateend'] = $value;
        }
    }

    /**
     * Set the extended data of the dataitem.
     *
     * Note that this method only sets the property on the model. To save the extended data into the database, the
     * `save()` method will need to be called.
     *
     * @param array $extendedData
     *   An associative array of the extended data key/value pairs.
     * @return void
     */
    public function setExtendedData($extendedData)
    {
        if (!empty($extendedData)) {
            $items = [];
            foreach ($extendedData as $key => $value) {
                $items[] = '<Data name="' . $key . '"><value><![CDATA[' . $value . ']]></value></Data>';
            }
            $this->extended_data = '<ExtendedData>' . implode('', $items) . '</ExtendedData>';
        } else {
            $this->extended_data = null;
        }
    }

    /**
     * Get the extended data of the dataitem.
     *
     * @return array|false|null
     *   Returns the array of extended data as key/value paris, or null if it's empty. Returns false if it's failed to
     *   parse the data.
     */
    public function getExtendedData()
    {
        if (!isset($this->extended_data)) {
            return null;
        }
        $extData = [];
        try {
            $extDataXML = simplexml_load_string($this->extended_data, 'SimpleXMLElement', LIBXML_NOCDATA);
            foreach ($extDataXML->Data as $item) {
                $extData[(string) $item->attributes()->name] = (string) $item->value;
            }
        } catch (Exception $e) {
            return false;
        }
        return $extData;
    }

    // extended data should be stored as the KML version, and then turned into a table, or json or whatever as needed in output.
    // The old way of adding it direction into 'description', will cause problems with when we want it to be possible to export 
    // the data with ids so person can update them again.
    public function extDataAsHTML()
    {
        $extData = $this->getExtendedData();
        if ($extData === null) {
            return null;
        } elseif ($extData === false) {
            return "Could not display extended data.";
        } else {
            $htext = "<dl class='extdata'>";
            foreach ($extData as $name => $value) {
                $htext = $htext . "<dt>" . $name . "</dt><dd>" . $this->sniffUrl($value) . "</dd></dt>";
            }
            $htext = $htext . "</dl>";
            return $htext;
        }
    }

    public function extDataAsKeyValues()
    {
        $extData = $this->getExtendedData();
        if ($extData === null) {
            return null;
        } elseif ($extData === false) {
            return "Could not convert extended data.";
        } else {
            $outpairs = array();
            foreach ($extData as $name => $value) {
                if (empty($name)) {
                    continue;
                }
                if ((strtolower($name) === "latitude") || (strtolower($name) === "longitude")) {
                    continue;
                }
                $outpairs[$name] = $this->sniffUrl($value);
            }
            return $outpairs;
        }
    }

    public function sniffUrl($maybeUrl)
    {
        if (str_starts_with($maybeUrl, 'http')) {
            return "<a href='" . $maybeUrl . "'>" . htmlentities($maybeUrl) . "</a>";
        } else {
            return htmlentities($maybeUrl);
        }
    }

    /**
     * Get the scope for search.
     *
     * This method will provide the scope for search. It will exclude dataitems which belong to private dataset.
     *
     * @return Builder
     *   The query builder of the search which can be further extended.
     */
    public static function searchScope()
    {
        return self::where(function ($query) {
            $query->whereHas('dataset', function ($q) {
                $q->where('public', '=', 'true');
            })->orWhere('dataset_id', null);
        });
    }

    /**
     * Get enumerated LGA values.
     *
     * @return string[]
     *   LGA names.
     */
    public static function getAllLga()
    {
        return self::getColumnEnumeration('lga');
    }

    /**
     * Get enumerated feature terms.
     *
     * @return string[]
     *   Feature term names.
     */
    public static function getAllFeatures()
    {
        return self::getColumnEnumeration('feature_term');
    }

    /**
     * Get enumerated parishes.
     *
     * @return string[]
     *   Parish names.
     */
    public static function getAllParishes()
    {
        return self::getColumnEnumeration('parish');
    }

    /**
     * Get enumerated states.
     *
     * @return string[]
     *   State names.
     */
    public static function getAllStates()
    {
        return self::getColumnEnumeration('state');
    }

    /**
     * Get enumerated values from a column.
     *
     * @param string $column
     *   The database column name.
     *
     * @return string[]
     *   The enumerated values.
     */
    private static function getColumnEnumeration($column)
    {
        return self::select($column)->distinct()->where($column, '<>', '')->pluck($column)->toArray();
    }

    /**
     * Generate GeoJSON of a dataitem
     * 
     * Could be merged with FileFormatter->toGeoJSON
     * 
     * @return string the generated GeoJSON.
     */
    public function json()
    {

        $metadata = array(
            'placeid' => $this->uid,
            'name' => isset($this->title) ? $this->title : $this->placename,
            'description' => $this->description,
            'url' => URL::full(),
        );

        // Set feature collection config.
        $featureCollectionConfig = new FeatureCollectionConfig();
        $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
        $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());
        $featureCollectionConfig->setInfoTitle($metadata['name'], $metadata['url']);

        //Set properties.
        $proppairs = array();
        // Set feature config.
        $featureConfig = new FeatureConfig();

        if (!empty($this->title)) {
            $proppairs["name"] = $this->title;
        } else {
            $proppairs["name"] = $this->placename;
        }
        if (!empty($this->placename)) {
            $proppairs["placename"] = $this->placename;
        }

        if (!empty($this->description)) {
            $proppairs["description"] = $this->description;
        }
        if (!empty($this->uid)) {
            $proppairs["id"] = $this->uid;
        }
        if (!empty($this->warning)) {
            $proppairs["warning"] = $this->warning;
        }
        if (!empty($this->state)) {
            $proppairs["state"] = $this->state;
        }
        if (!empty($this->parish)) {
            $proppairs["parish"] = $this->parish;
        }
        if (!empty($this->feature_term)) {
            $proppairs["feature_term"] = $this->feature_term;
        }
        if (!empty($this->lga)) {
            $proppairs["lga"] = $this->lga;
        }
        if (!empty($this->source)) {
            $proppairs["source"] = $this->source;
            $proppairs["original_data_source"] = $this->source;
        }
        if (!empty($this->datestart)) {
            $proppairs["datestart"] = $this->datestart;
        }
        if (!empty($this->dateend)) {
            $proppairs["dateend"] = $this->dateend;
        }

        $unixepochdates = $this->datestart . "";
        $unixepochdatee = $this->dateend . "";
        if (strpos($unixepochdates, '-') === false) {
            $unixepochdates = $unixepochdates . "-01-01";
        }
        if (strpos($unixepochdatee, '-') === false) {
            $unixepochdatee = $unixepochdatee . "-01-01";
        }

        if (!empty($this->datestart)) {
            $proppairs["udatestart"] = strtotime($unixepochdates) * 1000;
        }
        if (!empty($this->dateend)) {
            $proppairs["udateend"] = strtotime($unixepochdates) * 1000;
        }

        if (!empty($this->latitude)) {
            $proppairs["latitude"] = $this->latitude;
        }
        if (!empty($this->longitude)) {
            $proppairs["longitude"] = $this->longitude;
        }

        if (!empty($this->external_url)) {
            $proppairs["linkback"] = $this->external_url;
        }

        if (!empty($this->uid)) {
            $proppairs["TLCMapLinkBack"] = url("places/" . $this->uid);

            // Set footer link.
            $featureConfig->addLink("TLCMap Record: {$this->uid}", $proppairs["TLCMapLinkBack"]);
        }

        if (isset($this->dataset_id)) {
            $proppairs["TLCMapDataset"] = url("publicdatasets/" . $this->dataset_id);
        } else {
            $proppairs["TLCMapDataset"] = url("/");
        }
        // Set footer link.
        $featureConfig->addLink('TLCMap Layer', $proppairs["TLCMapDataset"]);

        if (!empty($this->extended_data)) {
            $proppairs = array_merge($proppairs, $this->extDataAsKeyValues());
        }

        $features[] = array(
            'type' => 'Feature',
            'geometry' => array(
                'type' => 'Point', 
                'coordinates' => array((float)$this->longitude, (float)$this->latitude)
            ),
            'properties' => $proppairs,
            'display' => $featureConfig->toArray(),
        );


        $res = array(
            'type' => 'FeatureCollection',
            'metadata' => $metadata,
            'features' => $features,
            'display' => $featureCollectionConfig->toArray()
        );
        return json_encode($res, JSON_PRETTY_PRINT);
    }

     /**
     * Generate CSV of a dataitem.
     *
     * Could be merged with FileFormatter->toGeoCSV
     *
     * @return string the generated CSV.
     */
    public function csv()
    {

        $f = fopen('php://memory', 'r+');
        $delimiter = ',';
        $enclosure = '"';
        $escape_char = "\\";
        // Exclude columns.
        $excludeColumns = ['uid', 'datasource_id'];

        $colheads = array();
        $extkeys = array();

        //Build header
        $arr = json_decode(json_encode($this, true));
        foreach ($arr as $key => $value) {
            if (!($value === NULL) && $key !== 'extended_data') {
                if (!in_array($key, $colheads) && !in_array($key, $excludeColumns)) {
                    $colheads[] = $key;
                }
            }
        }
        //Add extended data headers
        $arr = $this->getExtendedData();
        if (!empty($arr)) {
            foreach ($arr as $key => $value) {
                if (!($value === NULL)) {
                    if (!in_array($key, $colheads)) {
                        $colheads[] = $key;
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

        //Add data
        $cells = array();

        $vals = json_decode(json_encode($this), true);

        $ext = $this->getExtendedData();
        if (!empty($ext)) {
            $vals = $vals + $ext;
        }

        $vals["id"] = $this->uid;

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
        rewind($f);

        return stream_get_contents($f);
    }

    /**
     * Generate the KML of the dataitem.
     *
     * @return string
     *   The generated KML.
     */
    public function kml()
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $parNode = $dom->appendChild($dom->createElementNS('http://earth.google.com/kml/2.2', 'kml'));
        $docNode = $parNode->appendChild($dom->createElement('Document'));
        $docNode->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection('TLCMap'));

        //Setup
        $place = $docNode->appendChild($dom->createElement('Placemark'));
        $point = $place->appendChild($dom->createElement('Point'));
        $ed = $place->appendChild($dom->createElement('ExtendedData'));

        //HTML table for ED data - we reuse this for the ghap_url element
        $linkToItem = config('app.url');
        $linkToItem .= ($this->uid) ? ("/places/" . $this->uid) : '';
        $ed_table = "<br><br><table class='tlcmap'><tr><th>TLCMap</th><td><a href='{$linkToItem}'>{$linkToItem}</a></td></tr>";

        $linkToLayer = config('app.url');
        $linkToLayer .= ($this->dataset_id) ? "/publicdatasets/" . $this->dataset_id : '';
        $ed_table .= "<tr><th>TLCMap Layer</th><td><a href='{$linkToLayer}'>{$linkToLayer}</a></td></tr>";

        //Add lat/long to html data
        $ed_table .= "<tr><th>Latitude</th><td>{$this->latitude}</td></tr>";
        $ed_table .= "<tr><th>Longitude</th><td>{$this->longitude}</td></tr>";

        //Minimum Data
        $place->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection((isset($this->title)) ? $this->title : $this->placename));
        $description = $place->appendChild($dom->createElement('description'));
        $description->appendChild($dom->createCDATASection($this->description));
        $point->appendChild($dom->createElement('coordinates', $this->longitude . ',' . $this->latitude));

        //Extended Data - doing this manually so we can rename columns where appropriate
        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'id');
        $data->appendChild($dom->createElement('displayName', 'ID'));
        $data->appendChild($dom->createElement('value', $this->uid));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'state'); //state instead of state_code as this is our preferred var name
        $data->appendChild($dom->createElement('displayName', 'State'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($this->state));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'lga'); //lga instead of lga_name as this is our preferred var name
        $data->appendChild($dom->createElement('displayName', 'LGA'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($this->lga));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'parish');
        $data->appendChild($dom->createElement('displayName', 'Parish'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($this->parish));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'feature_term');
        $data->appendChild($dom->createElement('displayName', 'Feature Term'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($this->feature_term));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'flag');
        $data->appendChild($dom->createElement('displayName', 'Flag'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($this->flag));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'source');
        $data->appendChild($dom->createElement('displayName', 'Source'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($this->source));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        //Dataitems can handle calls to non existing keys, but collection items (register entries) cannot, so we must check isset()
        $datestart = (isset($this->datestart)) ? $this->datestart : '';
        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'datestart');
        $data->appendChild($dom->createElement('displayName', 'Date Start'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($datestart));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $dateend = (isset($this->dateend)) ? $this->dateend : '';
        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'dateend');
        $data->appendChild($dom->createElement('displayName', 'Date End'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($dateend));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $external_url = (isset($this->external_url)) ? $this->external_url : '';
        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'linkback_url');
        $data->appendChild($dom->createElement('displayName', 'Linkback'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($external_url));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'tlcm_url');
        $data->appendChild($dom->createElement('displayName', 'TLCMap'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($linkToItem));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        $data = $ed->appendChild($dom->createElement('Data'));
        $data->setAttribute('name', 'tlcm_ds');
        $data->appendChild($dom->createElement('displayName', 'TLCMap Layer'));
        $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($linkToLayer));
        $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

        //ED_TABLE
        $ed_table .= "</table>";
        $description->appendChild($dom->createCDATASection($ed_table));
        
        return $dom->saveXML();
    }
}
