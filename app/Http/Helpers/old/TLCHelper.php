<?php
/*
 * Benjamin McDonnell
 * For TLCMap project, University of Newcastle
 * 
 * Some helper classes for the RegisterController
 * keeping them here is much cleaner
 * 
 * OLD - IS THIS USED ANYWHERE?
 */

namespace App\Http\Helpers;

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

class TLCHelper
{
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
            Storage::disk('public')->put('/kml\/' . $randomstr . '/children/Child' . $count . '.kml', Self::toKML($file, $asoc_array)); //store kml's to disk
            $count++;
        }

        \Zipper::make($zipfilepath)->add($kmlfolderpath)->close(); //make the zip (stores it on server)

        File::deleteDirectory($kmlfolderpath); //delete the kml since we no longer need it

        return response()->download($zipfilepath)->deleteFileAfterSend(true); //return the zip of kml files then delete
    }

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

    public static function toCSV($results, $headers)
    {
        $callback = function () use ($results) {
            $columns = array("placename", "anps id", "state", "LGA", "Latitude", "Longitude", "Original Data Source", "flag", "description"); //unsure how to put multiple sources into csv
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($results as $result) {
                fputcsv($file, array($result->placename, $result->anps_id, $result->state_code, $result->lga_name, $result->TLCM_Latitude, $result->TLCM_Longitude, $result->ORIGINAL_DATA_SOURCE, $result->flag, $result->description));
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    public static function toGeoJSON2($results, $sources)
    { //receives $results (an array of string lines) and $sources (an array of arrays of source information)
        $original_data = json_decode($results, true); //decode a string array into an associative array, where key is the table column and value is the value in that column for this line, so we can pull out data by col name
        $features = array();

        foreach ($original_data as $key => $value) {
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => array((float)$value['TLCM_Longitude'], (float)$value['TLCM_Latitude'])),
                'properties' => array('name' => $value['placename'], 'id' => $value['anps_id']), 'state' => $value['state_code'],
                'LGA' => $value['lga_name'], 'Original Data Source' => $value['ORIGINAL_DATA_SOURCE'], 'flag' => $value['flag'],
                'description' => $value['description'], 'sources' => $sources[$value['anps_id']]
            );
        };

        $allfeatures = array('type' => 'FeatureCollection', 'features' => $features);
        return json_encode($allfeatures, JSON_PRETTY_PRINT);

    }

    public static function toGeoJSON($results, $sources)
    { //receives $results (an array of string lines) and $sources (an array of arrays of source information)
        $original_data = json_decode($results->getCollection(), true); //decode a string array into an associative array, where key is the table column and value is the value in that column for this line, so we can pull out data by col name
        $features = array();

        foreach ($original_data as $key => $value) {
            $features[] = array(
                'type' => 'Feature',
                'geometry' => array('type' => 'Point', 'coordinates' => array((float)$value['TLCM_Longitude'], (float)$value['TLCM_Latitude'])),
                'properties' => array('name' => $value['placename'], 'id' => $value['anps_id']), 'state' => $value['state_code'],
                'LGA' => $value['lga_name'], 'Original Data Source' => $value['ORIGINAL_DATA_SOURCE'], 'flag' => $value['flag'],
                'description' => $value['description'], 'sources' => $sources[$value['anps_id']]
            );
        };

        $allfeatures = array('type' => 'FeatureCollection', 'features' => $features);
        return json_encode($allfeatures, JSON_PRETTY_PRINT);

    }

    public static function toKML($results, $sources)
    {
        //setting up 
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = "<kml xmlns=\"http://www.opengis.net/kml/2.2\">";
        $kml[] = "<Document>";

        foreach ($results as $l) {
            $kml[] = "<Placemark>";
            $kml[] = "<name><![CDATA[" . $l->placename . "]]></name>";
            $kml[] = "<Point>";
            $kml[] = "<coordinates>" . $l->TLCM_Longitude . "," . $l->TLCM_Latitude . "</coordinates>"; //insert Lat and Long
            $kml[] = "</Point>";

            $kml[] = "<ExtendedData>";
            $kml[] = "<Data name = \"anps_id\">";
            $kml[] = "<displayName>ANPS ID</displayName>";
            $kml[] = "<value>" . $l->anps_id . "</value>";
            $kml[] = "</Data>";

            $kml[] = "<Data name = \"state_id\">";
            $kml[] = "<displayName>State ID</displayName>";
            $kml[] = "<value><![CDATA[" . $l->state_id . "]]></value>";    //insert state_id
            $kml[] = "</Data>";

            $kml[] = "<Data name = \"state\">";
            $kml[] = "<displayName>State</displayName>";
            $kml[] = "<value><![CDATA[" . $l->state_code . "]]></value>";    //insert state_code
            $kml[] = "</Data>";

            $kml[] = "<Data name = \"flag\">";
            $kml[] = "<displayName>Flag</displayName>";
            $kml[] = "<value><![CDATA[" . $l->flag . "]]></value>";    //insert flag
            $kml[] = "</Data>";

            $kml[] = "<Data name = \"original_source\">";
            $kml[] = "<displayName>Original Source</displayName>";
            $kml[] = "<value><![CDATA[" . $l->ORIGINAL_DATA_SOURCE . "]]></value>";    //insert original_source
            $kml[] = "</Data>";

            $kml[] = "<Data name = \"description\">";
            $kml[] = "<displayName>Description</displayName>";
            $kml[] = "<value><![CDATA[" . $l->description . "]]></value>";    //insert original_source
            $kml[] = "</Data>";

            $kml[] = "<Data name = \"sources\">";
            $count = 0;
            if (array_key_exists($l->anps_id, $sources)) {
                foreach ($sources[$l->anps_id] as $source) { //For each source present for the given anps_id
                    $count++;
                    $kml[] = "<Data name = \"source " . $count . "\">";

                    $kml[] = "<Data name = \"anps_source_id\">";
                    $kml[] = "<displayName>ANPS source id</displayName>";
                    $kml[] = "<value>" . $source->source_id . "</value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source type\">";
                    $kml[] = "<displayName>ANPS source_type</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->source_type . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_title\">";
                    $kml[] = "<displayName>ANPS source title</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->title . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_author\">";
                    $kml[] = "<displayName>ANPS source author</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->author . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_isbn\">";
                    $kml[] = "<displayName>ANPS source isbn</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->isbn . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_publisher\">";
                    $kml[] = "<displayName>ANPS source publisher</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->publisher . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_place\">";
                    $kml[] = "<displayName>ANPS source place</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->source_place . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_date\">";
                    $kml[] = "<displayName>ANPS source date</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->source_date . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_location\">";
                    $kml[] = "<displayName>ANPS source location</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->source_location . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_library\">";
                    $kml[] = "<displayName>ANPS source library</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->anps_library . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_status\">";
                    $kml[] = "<displayName>ANPS source status</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->source_status . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";

                    $kml[] = "<Data name = \"anps_source_notes\">";
                    $kml[] = "<displayName>ANPS source notes</displayName>";
                    $kml[] = "<value><![CDATA[" . $source->source_notes . "]]></value>";    //insert original_source
                    $kml[] = "</Data>";
                    $kml[] = "</Data>";
                }


            }
            $kml[] = "</Data>";


            $kml[] = "</ExtendedData>";
            $kml[] = "</Placemark>";
        }

        // End XML file
        $kml[] = "</Document>";
        $kml[] = "</kml>";
        $kmlOutput = join("\r\n", $kml);

        return $kmlOutput;
    }

    public static function toKMLWithFormat($results, $sources)
    {
        //setting up 
        $kml = array('<?xml version="1.0" encoding="UTF-8"?>');
        $kml[] = "<kml xmlns=\"http://www.opengis.net/kml/2.2\">";
        $kml[] = "\t<Document>";

        foreach ($results as $l) {
            $kml[] = "\t\t<Placemark>";
            $kml[] = "\t\t\t<name><![CDATA[" . $l->placename . "]]></name>";
            $kml[] = "\t\t\t<Point>";
            $kml[] = "\t\t\t\t<coordinates>" . $l->TLCM_Longitude . "," . $l->TLCM_Latitude . "</coordinates>"; //insert Lat and Long
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
            $kml[] = "\t\t\t\t\t<value><![CDATA[" . $l->ORIGINAL_DATA_SOURCE . "]]></value>";    //insert original_source
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