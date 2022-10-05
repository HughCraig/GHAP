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
use TLCMap\Models\Register;
use TLCMap\Models\Documentation;
use TLCMap\Models\Source;
use Illuminate\Support\Facades\Input;
use Response;
use File;
use DOMDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class FileFormatter
{
    /*
     *   When downloading large data, the user may wish to split the JSON file into smaller chunks 
     *       Some programs may not handle a single large file
     * 
     *   Will call the function to convert to geoJSON, then split the result into chunks, zip it, then send it back in the response
     */
    public static function jsonChunk($results, $chunks, $asoc_array)
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
            Storage::disk('public')->put('/json\/' . $randomstr . '/children/Child' . $count . '.json', Self::toGeoJSON2($file, $asoc_array)); //store json to disk
            $count++;
        }

        \Zipper::make($zipfilepath)->add($jsonfolderpath)->close(); //make the zip (stores it on server)

        File::deleteDirectory($jsonfolderpath); //delete the kml since we no longer need it

        return response()->download($zipfilepath)->deleteFileAfterSend(true); //return the zip of kml files then delete
    }

    /*
     *  Similarly as above, but for KML - Also uses a KML Master file to reference all of the sub-files
     */
    public static function kmlChunk($results, $chunks, $asoc_array)
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
            Storage::disk('public')->put('/kml\/' . $randomstr . '/children/Child' . $count . '.kml', Self::toKML2($file, $asoc_array)); //store kml's to disk
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
     *  Convert search result to csv format, send it back in the response
     * Broken, rework for dataitems, gaps in strings, etc
     */
    public static function toCSV($results, $headers)
    {
        $callback = function () use ($results) {
            $columns = array("id", "title", "placename", "state", "lga", "parish", "feature_term", "latitude", "longitude", "source", "flag", "description", "datestart", "dateend", "linkback", "tlcmaplink", "layerlink"); //unsure how to put multiple sources into csv
            $file = fopen('php://output', 'w');

            fputcsv($file, $columns);
            foreach ($results as $r) {
                //      Log::Error("ASDF r is " . json_encode($r));
                //       Log::Error("ASDF 2222  " . json_encode($r->title) . " 33333 " . json_encode($r->placename));
                //        Log::Error(" ASDF 44444 " . property_exists($r, 'title'));
                // this is a bit wierd. It seems that if the results are from user layer, they are key value pairs so $r['title'] works, but if they are from ANPS they are properties of an object and only $r->title works
                $title = (!empty($r->title)) ? $r->title : $r->placename;
                $source = "";
                if (!empty($r->original_data_source)) {
                    $source = $r->original_data_source;
                } elseif (!empty($r->source)) {
                    $source = $r->source;
                }
                // $source = (!empty($r->original_data_source)) ? $r->original_data_source : $r->source;
                $id = (isset($r->anps_id)) ? "a" . base_convert($r->anps_id, 10, 16) : "t" . base_convert($r->dataitem_id, 10, 16);
                $state = (isset($r->state_code)) ? $r->state_code : $r->state;
                $datestart = (isset($r->datestart)) ? $r->datestart : '';
                $dateend = (isset($r->dateend)) ? $r->dateend : '';
                $external_url = (isset($r->external_url)) ? $r->external_url : '';
                $ghap_url = env('APP_URL');
                $ghap_url .= (isset($r->anps_id)) ? "/search?id=a" . base_convert($r->anps_id, 10, 16) : "/search?id=t" . base_convert($r->dataitem_id, 10, 16);
                $layerlink = env('APP_URL');
                $layerlink .= (isset($r->anps_id)) ? "" : "/publicdatasets/" . $r->dataset_id;
                fputcsv($file, array($id, $title, $r->placename, $state, $r->lga_name, $r->parish, $r->feature_term, $r->tlcm_latitude, $r->tlcm_longitude, $source, $r->flag, $r->description, $datestart, $dateend, $external_url, $ghap_url, $layerlink));
            }

            fclose($file);

        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Takes a LengthAwarePaginator $results, and an array $sources where $sources[##] is the anps id that source belongs to
     * Returns a JSON representation of the data, in geoJSON compatible format
     *
     * Reworked to handle public dataitems
     * !!! could be merged with DatasetController->generateJSON
     */
    public static function toGeoJSON($results, $sources)
    {
        $features = array();


        foreach ($results as $r) {

            $proppairs = array();
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
            if (!empty($r->anps_id)) {
                $id = "a" . base_convert($r->anps_id, 10, 16);
            }
            if (!empty($r->dataitem_id)) {
                $id = "t" . base_convert($r->dataitem_id, 10, 16);
            }
            $proppairs["id"] = $id;
            if (!empty($r->warning)) {
                $proppairs["warning"] = $r->warning;
            }
            if (!empty($r->state_code)) {
                $proppairs["state"] = $r->state_code;
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
            }
            //
            if (!empty($sources[$r->anps_id])) {
                $proppairs["anps_sources"] = $sources[$r->anps_id];
            }
            if (!empty($r->source)) {
                $proppairs["original_data_source"] = $r->source;
            }
            if (!empty($r->original_data_source)) {
                $proppairs["original_data_source"] = $r->original_data_source;
            }


            if (!empty($contribution)) {
                $proppairs["contribution"] = $contribution;
            }
            if (!empty($r->datestart)) {
                $proppairs["datestart"] = $r->datestart;
            } elseif (!empty($r->tlcm_start)) {
                $proppairs["datestart"] = $r->tlcm_start;
            }
            if (!empty($r->dateend)) {
                $proppairs["dateend"] = $r->dateend;
            } elseif (!empty($r->tlcm_end)) {
                $proppairs["dateend"] = $r->tlcm_end;
            }

            if (!empty($r->datestart)) {
                $proppairs["latitude"] = $r->latitude;
            } elseif (!empty($r->tlcm_latitude)) {
                $proppairs["latitude"] = $r->tlcm_latitude;
            }
            if (!empty($r->dateend)) {
                $proppairs["longitude"] = $r->longitude;
            } elseif (!empty($r->tlcm_longitude)) {
                $proppairs["longitude"] = $r->tlcm_longitude;
            }

            if (!empty($r->external_url)) {
                $proppairs["linkback"] = $r->external_url;
            }

            $proppairs["TLCMapLinkBack"] = url("search?id=" . $id);


            // Log::error("asdf" . json_encode($r));

            $dataset_url = env('APP_URL');
            if (isset($r->dataset_id)) {
                // $dataset_url = env('APP_URL') . "/publicdatasets/" . $r->dataset_id;
                $proppairs["TLCMapDataset"] = url("publicdatasets/" . $r->dataset_id);
            } else {
                $proppairs["TLCMapDataset"] = url("/");
            }
            // $proppairs["layer"] = $dataset_url;

            if (!empty($r->extended_data)) {
                $proppairs = array_merge($proppairs, $r->extDataAsKeyValues());
                //$proppairs["extended_data"] = $r->extDataAsHTML();
            }

            $metadata = array('name' => 'TLCMap Gazetteer Query', 'url' => URL::full());


            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => array((float)$r->tlcm_longitude, (float)$r->tlcm_latitude)),
                'properties' => $proppairs);


        };
        if (!isset($metadata)) {
            return "No search results to display.";
        }

        $allfeatures = array('type' => 'FeatureCollection', 'metadata' => $metadata, 'features' => $features);
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
    public static function toKML2($results, $sources, $parameters)
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
            $linkToItem .= ($r->anps_id) ? "/search?id=a" . base_convert($r->anps_id, 10, 16) : "/search?id=t" . base_convert($r->dataitem_id, 10, 16);
            $ed_table = "<br><br><table class='tlcmap'><tr><th>TLCMap</th><td><a href='{$linkToItem}'>{$linkToItem}</a></td></tr>";

            $linkToLayer = env('APP_URL');
            $linkToLayer .= ($r->anps_id) ? "" : "/publicdatasets/" . $r->dataset_id;
            $ed_table .= "<tr><th>TLCMap Layer</th><td><a href='{$linkToLayer}'>{$linkToLayer}</a></td></tr>";

            //Add lat/long to html data
            $ed_table .= "<tr><th>Latitude</th><td>{$r->tlcm_latitude}</td></tr>";
            $ed_table .= "<tr><th>Longitude</th><td>{$r->tlcm_longitude}</td></tr>";

            //Minimum Data
            $place->appendChild($dom->createElement('name'))->appendChild($dom->createCDATASection((isset($r->title)) ? $r->title : $r->placename));
            $description = $place->appendChild($dom->createElement('description'));
            $description->appendChild($dom->createCDATASection($r->description));
            $point->appendChild($dom->createElement('coordinates', $r->tlcm_longitude . ',' . $r->tlcm_latitude));

            //Extended Data - doing this manually so we can rename columns where appropriate
            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'id');
            $data->appendChild($dom->createElement('displayName', 'ID'));
            if ($r->anps_id) $data->appendChild($dom->createElement('value', 'a' . base_convert($r->anps_id, 10, 16)));
            else $data->appendChild($dom->createElement('value', 't' . base_convert($r->dataitem_id, 10, 16)));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'state'); //state instead of state_code as this is our preferred var name
            $data->appendChild($dom->createElement('displayName', 'State'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->state_code));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'lga'); //lga instead of lga_name as this is our preferred var name
            $data->appendChild($dom->createElement('displayName', 'LGA'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->lga_name));
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
            if ($r->anps_id) $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->flag));
            else $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection('From Contributed Layer ' . $r->flag));
            $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

            $data = $ed->appendChild($dom->createElement('Data'));
            $data->setAttribute('name', 'source');
            $data->appendChild($dom->createElement('displayName', 'Source'));
            $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($r->original_data_source));
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

            // CANNOT NEST DATA IN EXTENDED DATA - SCRAP THE SOURCES NODE
            // $sourcesNode = $ed->appendChild( $dom->createElement( 'Data' ) );
            // $sourcesNode->setAttribute('name', 'sources');

            //ANPS Sources
            $count = 0;
            if (array_key_exists($r->anps_id, $sources)) {
                foreach ($sources[$r->anps_id] as $source) { //For each source present for the given anps_id
                    $count++;
                    // $src = $sourcesNode->appendChild( $dom->createElement( 'Data' ) );
                    // $src->setAttribute('name', 'source_' . $count);

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_id');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' ID'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_id));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_type');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Type'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_type));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_title');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Title'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->title));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_author');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Author'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->author));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_isbn');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' ISBN'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->isbn));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_publisher');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Publisher'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->publisher));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_place');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Place'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_place));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_date');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Date'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_date));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_location');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Location'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_location));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_library');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Library'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->anps_library));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_status');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Status'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_status));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";

                    $data = $ed->appendChild($dom->createElement('Data'));
                    $data->setAttribute('name', 'anps_source_' . $count . '_notes');
                    $data->appendChild($dom->createElement('displayName', 'ANPS Source ' . $count . ' Notes'));
                    $data->appendChild($dom->createElement('value'))->appendChild($dom->createCDATASection($source->source_notes));
                    $ed_table .= "<tr><th>{$data->firstChild->nodeValue}</th><td>{$data->firstChild->nextSibling->nodeValue}</td></tr>";
                }
            }

            //ED_TABLE
            $ed_table .= "</table>";
            $description->appendChild($dom->createCDATASection($ed_table));
        }
        return $dom->saveXML();
    }


    /*
     *  KML format WITH pretty-print (not necessary)
     */
    public static function toKMLWithFormat($results, $sources)
    {
        //setting up 
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = "<kml xmlns=\"http://www.opengis.net/kml/2.2\">";
        $kml[] = "\t<Document>";

        foreach ($results as $l) {
            $kml[] = "\t\t<Placemark>";
            $kml[] = "\t\t\t<name><![CDATA[" . $l->title . "]]></name>";
            $kml[] = "\t\t\t<Point>";
            $kml[] = "\t\t\t\t<coordinates>" . $l->tlcm_longitude . "," . $l->tlcm_latitude . "</coordinates>"; //insert Lat and Long
            $kml[] = "\t\t\t</Point>";

            $kml[] = "\t\t\t<ExtendedData>";
            $kml[] = "\t\t\t\t<Data name = \"anps_id\">";
            $kml[] = "\t\t\t\t\t<displayName>ANPS ID</displayName>";
            $kml[] = "\t\t\t\t\t<value>" . $l->anps_id . "</value>";
            $kml[] = "\t\t\t\t</Data>";

            $kml[] = "\t\t\t\t<Data name = \"state_id\">";
            $kml[] = "\t\t\t\t\t<displayName>State ID</displayName>";
            $kml[] = "\t\t\t\t\t<value><![CDATA[" . $l->state_id . "]]></value>";    //insert state_id
            $kml[] = "\t\t\t\t</Data>";

            $kml[] = "\t\t\t\t<Data name = \"state\">";
            $kml[] = "\t\t\t\t\t<displayName>State</displayName>";
            $kml[] = "\t\t\t\t\t<value><![CDATA[" . $l->state_code . "]]></value>";    //insert state_code
            $kml[] = "\t\t\t\t</Data>";

            $kml[] = "\t\t\t\t<Data name = \"flag\">";
            $kml[] = "\t\t\t\t\t<displayName>Flag</displayName>";
            $kml[] = "\t\t\t\t\t<value><![CDATA[" . $l->flag . "]]></value>";    //insert flag
            $kml[] = "\t\t\t\t</Data>";

            $kml[] = "\t\t\t\t<Data name = \"original_source\">";
            $kml[] = "\t\t\t\t\t<displayName>Original Source</displayName>";
            $kml[] = "\t\t\t\t\t<value><![CDATA[" . $l->original_data_source . "]]></value>";    //insert original_source
            $kml[] = "\t\t\t\t</Data>";

            $kml[] = "\t\t\t\t<Data name = \"description\">";
            $kml[] = "\t\t\t\t\t<displayName>Description</displayName>";
            $kml[] = "\t\t\t\t\t<value><![CDATA[" . $l->description . "]]></value>";    //insert original_source
            $kml[] = "\t\t\t\t</Data>";

            $kml[] = "\t\t\t\t<Data name = \"sources\">";
            $count = 0;
            if (array_key_exists($l->anps_id, $sources)) {
                foreach ($sources[$l->anps_id] as $source) { //For each source present for the given anps_id
                    $count++;
                    $kml[] = "\t\t\t\t\t<Data name = \"source " . $count . "\">";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_id\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source id</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value>" . $source->source_id . "</value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source type\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source_type</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->source_type . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_title\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source title</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->title . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_author\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source author</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->author . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_isbn\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source isbn</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->isbn . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_publisher\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source publisher</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->publisher . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_place\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source place</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->source_place . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_date\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source date</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->source_date . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_location\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source location</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->source_location . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_library\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source library</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->anps_library . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_status\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source status</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->source_status . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";

                    $kml[] = "\t\t\t\t\t\t<Data name = \"anps_source_notes\">";
                    $kml[] = "\t\t\t\t\t\t\t<displayName>ANPS source notes</displayName>";
                    $kml[] = "\t\t\t\t\t\t\t<value><![CDATA[" . $source->source_notes . "]]></value>";    //insert original_source
                    $kml[] = "\t\t\t\t\t\t</Data>";
                    $kml[] = "\t\t\t\t\t</Data>";
                }
                $kml[] = "\t\t\t\t</Data>";

            }


            $kml[] = "\t\t\t</ExtendedData>";
            $kml[] = "\t\t</Placemark>";
        }

        // End XML file
        $kml[] = "\t</Document>";
        $kml[] = "</kml>";
        $kmlOutput = join("\r\n", $kml);

        return $kmlOutput;
    }
}
