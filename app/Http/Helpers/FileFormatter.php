<?php
/*
 * Benjamin McDonnell
 * For TLCMap project, University of Newcastle
 * 
 * Some helper methods for the RegisterController
 * Converting the output into chunks, geoJson, KML, etc
 */

namespace TLCMap\Http\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Response;
use File;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use TLCMap\ViewConfig\FeatureCollectionConfig;
use TLCMap\ViewConfig\FeatureConfig;
use TLCMap\ViewConfig\GhapConfig;

class FileFormatter
{
    /*
     *   When downloading large data, the user may wish to split the JSON file into smaller chunks 
     *       Some programs may not handle a single large file
     * 
     *   Will call the function to convert to geoJSON, then split the result into chunks, zip it, then send it back in the response
     */
    public static function jsonChunk($results, $chunks)
    {
        $randomstr = 'tlc_' . Str::random(32);
        $files = $results->chunk(ceil($results->count() / $chunks)); //split into n chunks

        //Folders and pathing vars
        $folder = "storage/app/public/";
        $zipfilepath = base_path($folder . 'zip\\\\' . $randomstr . '.zip');
        $jsonfolderpath = base_path($folder . 'json\\\\' . $randomstr);

        //$master = Self::jsonMaster($files,$folder);
        //Storage::disk('public')->put('/json\/' . $randomstr . '/master.json', $master); //store kml master to disk

        $count = 1;
        foreach ($files as $file) { //make and store a kml for each child
            Storage::disk('public')->put('/json\/' . $randomstr . '/children/Child' . $count . '.json', Self::toGeoJSON2($file)); //store json to disk
            $count++;
        }

        \Zipper::make($zipfilepath)->add($jsonfolderpath)->close(); //make the zip (stores it on server)

        File::deleteDirectory($jsonfolderpath); //delete the kml since we no longer need it

        return response()->download($zipfilepath)->deleteFileAfterSend(true); //return the zip of kml files then delete
    }

    /*
     *  Similarly as above, but for KML - Also uses a KML Master file to reference all of the sub-files
     */
    public static function kmlChunk($results, $chunks)
    {
        $randomstr = 'tlc_' . Str::random(32);
        $files = $results->chunk(ceil($results->count() / $chunks)); //split into n chunks

        //Folders and pathing vars
        $folder = "storage/app/public/";
        $zipfilepath = base_path($folder . 'zip\\\\' . $randomstr . '.zip');
        $kmlfolderpath = base_path($folder . 'kml\\\\' . $randomstr);

        $master = Self::kmlMaster($files, $folder);
        Storage::disk('public')->put('/kml\/' . $randomstr . '/master.kml', $master); //store kml master to disk

        $count = 1;
        foreach ($files as $file) { //make and store a kml for each child
            Storage::disk('public')->put('/kml\/' . $randomstr . '/children/Child' . $count . '.kml', Self::toKML2($file)); //store kml's to disk
            $count++;
        }

        \Zipper::make($zipfilepath)->add($kmlfolderpath)->close(); //make the zip (stores it on server)

        File::deleteDirectory($kmlfolderpath); //delete the kml since we no longer need it

        return response()->download($zipfilepath)->deleteFileAfterSend(true); //return the zip of kml files then delete
    }

    /*
     *  KML formatting allows for multiple child kml files referenced in a single KML Master file
     * 
     *  Builds the master file referencing the child files
     */
    public static function kmlMaster($children, $root)
    {
        /*
            * $children is an array of children
            * returns an array of strings representing the kml master file that links to its children
            */
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = "<kml xmlns=\"http://www.opengis.net/kml/2.2\">";
        $kml[] = "<Document>";
        $count = 1;
        foreach ($children as $child) {
            $kml[] = "<NetworkLink>";
            $kml[] = "<name>Child " . $count . "</name>";
            $kml[] = "<Link><href>Children/Child" . $count . ".kml</href></Link>";
            $count++; //increment count
            $kml[] = "</NetworkLink>";
        }
        $kml[] = "</Document>";
        $kml[] = "</kml>";

        return $kml;
    }

    /*
     * Convert search result to csv format, send it back in the response
     * Broken, rework for dataitems, gaps in strings, etc
     *
     * @return
     *   The streamed the response of the CSV file.
     */
    public static function toCSV($results, $headers)
    {
        $callback = function () use ($results) {
            $file = fopen('php://output', 'w');
            FileFormatter::addCSVContent($file, $results);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Convert search result to CSV and return the content of the CSV.
     *
     * This method is different from 'toCSV' as it will return the content of the CSV instead of a streamed response.
     *
     * @param array $results
     *   The search results.
     * @return false|string
     *   The CSV content.
     */
    public static function toCSVContent($results)
    {
        $file = fopen('php://memory', 'r+');
        FileFormatter::addCSVContent($file, $results);
        rewind($file);
        return stream_get_contents($file);
    }

    /**
     * Add the content to the CSV file.
     *
     * Note: this method doesn't close the file handle. It's the responsibility of the code which calls this
     * method to close the file handle.
     *
     * @param $file
     *   The file handle of the CSV file.
     * @param array $results
     *   The search results data.
     * @return void
     */
    private static function addCSVContent($file, $results)
    {
        //unsure how to put multiple sources into csv
        $columns = array("id", "title", "placename", "state", "lga", "parish", "feature_term", "latitude", "longitude", "source", "flag", "description", "datestart", "dateend", "linkback", "tlcmaplink", "layerlink");
        fputcsv($file, $columns);
        foreach ($results as $r) {
            // this is a bit wierd. It seems that if the results are from user layer, they are key value pairs so $r['title'] works, but if they are from ANPS they are properties of an object and only $r->title works
            $title = (!empty($r->title)) ? $r->title : $r->placename;
            $source = (!empty($r->source)) ? $r->source : '';
            $id = (isset($r->uid)) ? $r->uid : '';
            $state = (isset($r->state)) ? $r->state : '';
            $datestart = (isset($r->datestart)) ? $r->datestart : '';
            $dateend = (isset($r->dateend)) ? $r->dateend : '';
            $external_url = (isset($r->external_url)) ? $r->external_url : '';
            $ghap_url = env('APP_URL');
            $ghap_url .= (isset($r->uid)) ? "/search?id=" . $r->uid : '';
            $layerlink = env('APP_URL');
            $layerlink .= (isset($r->dataset_id)) ? ("/publicdatasets/" . $r->dataset_id) : '';
            fputcsv($file, array($id, $title, $r->placename, $state, $r->lga, $r->parish, $r->feature_term, $r->latitude, $r->longitude, $source, $r->flag, $r->description, $datestart, $dateend, $external_url, $ghap_url, $layerlink));
        }
    }

    /**
     * Takes a LengthAwarePaginator $results, and an array $sources where $sources[##] is the anps id that source belongs to
     * Returns a JSON representation of the data, in geoJSON compatible format
     * 
     * @param  array $parameters   Optional parameters to customize the output, with possible keys as follows:
     *                             - 'line': When set, a LineString feature will be added to the GeoJSON connecting all points.
     *
     * Reworked to handle public dataitems
     * !!! could be merged with DatasetController->generateJSON
     */
    public static function toGeoJSON($results , $parameters = null)
    {
        $features = array();

        // Set feature collection config.
        $featureCollectionConfig = new FeatureCollectionConfig();
        $featureCollectionConfig->setBlockedFields(GhapConfig::blockedFields());
        $featureCollectionConfig->setFieldLabels(GhapConfig::fieldLabels());

        foreach ($results as $r) {

            $proppairs = array();

            // Set feature config.
            $featureConfig = new FeatureConfig();

            if (!empty($r->title)) {
                $proppairs["name"] = $r->title;
            } else {
                $proppairs["name"] = $r->placename;
            }
            if (!empty($r->placename)) {
                $proppairs["placename"] = $r->placename;
            }

            if (!empty($r->description)) {
                $proppairs["description"] = $r->description;
            }
            if (!empty($r->uid)) {
                $proppairs["id"] = $r->uid;
            }
            if (!empty($r->warning)) {
                $proppairs["warning"] = $r->warning;
            }
            if (!empty($r->state)) {
                $proppairs["state"] = $r->state;
            }
            if (!empty($r->parish)) {
                $proppairs["parish"] = $r->parish;
            }
            if (!empty($r->feature_term)) {
                $proppairs["feature_term"] = $r->feature_term;
            }
            if (!empty($r->lga)) {
                $proppairs["lga"] = $r->lga;
            }
            if (!empty($r->source)) {
                $proppairs["source"] = $r->source;
                $proppairs["original_data_source"] = $r->source;
            }
            if (!empty($r->datestart)) {
                $proppairs["datestart"] = $r->datestart;
            }
            if (!empty($r->dateend)) {
                $proppairs["dateend"] = $r->dateend;
            }

            $unixepochdates = $r->datestart . "";
            $unixepochdatee = $r->dateend . "";
            if (strpos($unixepochdates, '-') === false) {
                $unixepochdates = $unixepochdates . "-01-01";
            }
            if (strpos($unixepochdatee, '-') === false) {
                $unixepochdatee = $unixepochdatee . "-01-01";
            }

            if (!empty($r->datestart)) {
                $proppairs["udatestart"] = strtotime($unixepochdates) * 1000;
            }
            if (!empty($r->dateend)) {
                $proppairs["udateend"] = strtotime($unixepochdates) * 1000;
            }

            if (!empty($r->latitude)) {
                $proppairs["latitude"] = $r->latitude;
            }
            if (!empty($r->longitude)) {
                $proppairs["longitude"] = $r->longitude;
            }

            if (!empty($r->external_url)) {
                $proppairs["linkback"] = $r->external_url;
            }

            if (!empty($r->uid)) {
                $proppairs["TLCMapLinkBack"] = url("search?id=" . $r->uid);

                // Set footer link.
                $featureConfig->addLink("TLCMap Record: {$r->uid}", $proppairs["TLCMapLinkBack"]);
            }

            $dataset_url = env('APP_URL');
            if (isset($r->dataset_id)) {
                $proppairs["TLCMapDataset"] = url("publicdatasets/" . $r->dataset_id);
            } else {
                $proppairs["TLCMapDataset"] = url("/");
            }
            // Set footer link.
            $featureConfig->addLink('TLCMap Layer', $proppairs["TLCMapDataset"]);

            if (!empty($r->extended_data)) {
                $proppairs = array_merge($proppairs, $r->extDataAsKeyValues());
            }

            $metadata = array('name' => 'TLCMap Gazetteer Query', 'url' => URL::full());


            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => array((float)$r->longitude, (float)$r->latitude)),
                'properties' => $proppairs,
                'display' => $featureConfig->toArray(),
            );


        }
        if (!isset($metadata)) {
            return "No search results to display.";
        }

        if (isset($parameters) && isset($parameters['line'])) {

            $linecoords = array();

            foreach ($results as $i) {
                array_push($linecoords, [$i->longitude, $i->latitude]);
            }

            // Set line feature config.
            $featureConfig = new FeatureConfig();
            $featureConfig->setAllowedFields([]);

            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'LineString', 'coordinates' => $linecoords),
                'display' => $featureConfig->toArray(),
            );
        }

        $allfeatures = array(
            'type' => 'FeatureCollection',
            'metadata' => $metadata,
            'features' => $features,
            'display' => $featureCollectionConfig->toArray()
        );
        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }

    /**
     * https://developers.google.com/kml/articles/phpmysqlkml
     *
     * Remodelled to use DOMDocument instead of directly building xml from string
     *  This version is also compatible with the new User Public Dataset items
     *
     * TODO:    KML should have the extended data as an html table in the Description field
     * TODO:    The URL of the entity is not included. It should the unique address of that place within the gazetteer.
     */
    public static function toKML2($results, $parameters)
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $parNode = $dom->appendChild($dom->createElementNS('http://earth.google.com/kml/2.2', 'kml'));
        $docNode = $parNode->appendChild($dom->createElement('Document'));

        //Put the search query here?
        if ($parameters['fuzzyname']) $name = $parameters['fuzzyname'];
        else if ($parameters['name']) $name = $parameters['name'];
        else $name = 'TLCMap';
        $docNode->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection($name));

        //Iterate results
        foreach ($results as $r) {
            //Setup
            $place = $docNode->appendChild($dom->createElement('Placemark'));
            $point = $place->appendChild($dom->createElement('Point'));
            $ed = $place->appendChild($dom->createElement('ExtendedData'));

            //HTML table for ED data - we reuse this for the ghap_url element
            $linkToItem = env('APP_URL');
            $linkToItem .= ($r->uid) ? "/search?id=" . $r->uid : '';
            $ed_table = "<br><br><table class='tlcmap'><tr><th>TLCMap</th><td><a href='{$linkToItem}'>{$linkToItem}</a></td></tr>";

            $linkToLayer = env('APP_URL');
            $linkToLayer .= ($r->dataset_id) ? "/publicdatasets/" . $r->dataset_id : '';
            $ed_table .= "<tr><th>TLCMap Layer</th><td><a href='{$linkToLayer}'>{$linkToLayer}</a></td></tr>";

            //Add lat/long to html data
            $ed_table .= "<tr><th>Latitude</th><td>{$r->latitude}</td></tr>";
            $ed_table .= "<tr><th>Longitude</th><td>{$r->longitude}</td></tr>";

            //Minimum Data
            $place->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection((isset($r->title)) ? $r->title : $r->placename));
            $description = $place->appendChild($dom->createElement('description'));
            $description->appendChild($dom->createCDATASection($r->description));
            $point->appendChild($dom->createElement('coordinates', $r->longitude . ',' . $r->latitude));

            //Extended Data - doing this manually so we can rename columns where appropriate
            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'id');
            $data->appendChild($dom->createElement('displayName', 'ID'));
            $data->appendChild($dom->createElement('value', $r->uid));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'state'); //state instead of state_code as this is our preferred var name
            $data->appendChild($dom->createElement('displayName', 'State'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->state));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'lga'); //lga instead of lga_name as this is our preferred var name
            $data->appendChild($dom->createElement('displayName', 'LGA'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->lga));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'parish');
            $data->appendChild($dom->createElement('displayName', 'Parish'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->parish));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'feature_term');
            $data->appendChild($dom->createElement('displayName', 'Feature Term'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->feature_term));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'flag');
            $data->appendChild($dom->createElement('displayName', 'Flag'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->flag));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'source');
            $data->appendChild($dom->createElement('displayName', 'Source'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->source));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            //Dataitems can handle calls to non existing keys, but collection items (register entries) cannot, so we must check isset()
            $datestart = (isset($r->datestart)) ? $r->datestart : '';
            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'datestart');
            $data->appendChild($dom->createElement('displayName', 'Date Start'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($datestart));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $dateend = (isset($r->dateend)) ? $r->dateend : '';
            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'dateend');
            $data->appendChild($dom->createElement('displayName', 'Date End'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($dateend));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $external_url = (isset($r->external_url)) ? $r->external_url : '';
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
        }
        return $dom->saveXML();
    }

}
