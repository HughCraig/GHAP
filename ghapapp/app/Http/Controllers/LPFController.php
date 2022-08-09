<?php
/*
 * Benjamin McDonnell
 * For TLCMap project, University of Newcastle
 * 
 * Outputting the Gazetteer as an LPF format, so that it may be imported into Recogito
 * 
 * Manually builds a lot of the content - will be re-developed in the future to be more robust, handle additional tags, etc
 * 
 * Note: The full Gazetteer as LPF will be quite a large text file, for smaller samples change the $from_id and $to_id variables
 */

namespace TLCMap\Http\Controllers;

use Illuminate\Http\Request;
use TLCMap\Models\Register;
use TLCMap\Models\Documentation;
use TLCMap\Models\Source;
use Illuminate\Support\Facades\Input;
use Response;
use File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TLCMap\Http\Helpers\FileFormatter;
use TLCMap\Http\Helpers\SearchHelper;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Storage;

class LPFController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); //only let logged in users access'
    }

    /*
     *  The main function of this controller.
     *  Outputs the Gazetteer table in an JSON-LD LPF compliant format - So it may be imported into Recogito.
     * 
     *  Uses the helper functions below to keep this cleaner
     */
    public function gazToLPF()
    {
        //Setup
        $filename = 'gaz_large.lpf.json';
        $fileloc = ''; //will default to 'C:\Users\bjm662\Lavarel\ANPSWebServiceAPI\public'
        $search_page = 'http://www.tlcmap.org/ghap/search'; //?anps_id=12345
        $contents = $this->header(); //var for output string
        $first = true;
        $chunk_size = 100000; //how many register entries to do in each chunk - higher chunk size is faster but uses more memory
        $from_id = 0;
        $to_id = 2000000; //db has 314360 entries as of 16/01/2020 - use 2 mil+ as the max to future proof

        $source_table = DB::table('gazetteer.source')->join('documentation', 'documentation.doc_source_id', '=', 'source.source_id')
            ->get(array('anps_id', 'source_id', 'title', 'isbn', 'issue')); //Get source information from the source/documentation tables
        $grouped_sources = $source_table->groupBy('anps_id'); //a different collection for each

        //Get the gaz register DB, chunk it to save memory, use a reference to the variable '$contents'
        DB::table('gazetteer.register')->where([['anps_id', '>=', $from_id], ['anps_id', '<=', $to_id]])->orderBy('anps_id')
            ->chunk($chunk_size, function ($register) use (&$contents, $search_page, $grouped_sources, &$first) { //For each chunk
                foreach ($register as $row) { //for each row in DB
                    if (!$first) $contents .= ','; //this will be the comma at the END of each row
                    else $first = false;

                    $contents .= $this->properties($row->anps_id, $row->placename, $search_page); //Using the helper functions defined below to build the LPF

                    //Pushing source data into $sources array - Source data contains information gathered by the ANPS about where they sourced information for certain entries
                    $sources = [['label' => 'ANPS Gazetteer', 'link' => 'https://www.anps.org.au/']];
                    if ($row->original_data_source == 'ANPS Research') {
                        foreach ($grouped_sources[$row->anps_id] as $source) {
                            $link = 'source_id: ' . $source->source_id;
                            if ($source->isbn != 0) $link .= ', ISBN: ' . $source->isbn;
                            if ($source->issue != '') $link .= ', Issue: ' . $source->issue;
                            array_push($sources, ['label' => $source->title,
                                'link' => $link]);
                        }
                        //$sources = [['label' => 'test1', 'link' => 'http://placeholder1.link'],['label' => 'test2', 'link' => 'http://placeholder2.link']];
                    } else array_push($sources, ['label' => $row->original_data_source, 'link' => $row->source_link]);
                    $contents .= $this->names($row->placename, $sources); //placeholder, needs sources

                    $certainty = ($row->flag == 'Placeholder coordinates for uncertain location in anps data in lat/long columns'
                        || $row->flag == 'Only accurate to LGA' || $row->original_data_source == '') ? 'uncertain' : 'certain'; //choose certainty based on flags for this row

                    $contents .= $this->geometry($row->tlcm_longitude, $row->tlcm_latitude, $certainty); //placeholder

                    $contents .= $this->descriptions($row->anps_id, $row->description, $search_page);

                    $contents .= "\t\t}";

                }
            });

        $contents .= "\r\n\t" . ']'; //closing "features": []
        $contents .= "\r\n" . '}'; //closing file

        //Store file using php
        $file = fopen($fileloc . $filename, 'w');//opens file in append mode  
        fwrite($file, $contents);
        fclose($file);
    }

    /*
     *  Helper functions to build the LPF section by section
     */

    function header()
    {
        return '{' . "\r\n" .
            "\t" . '"type": "FeatureCollection",' . "\r\n" .
            "\t" . '"@context": "https://raw.githubusercontent.com/LinkedPasts/linked-places/master/linkedplaces-context-v1.jsonld",' . "\r\n" .
            "\t" . '"features": [' . "\r\n" .
            "\t\t";
    }

    function properties($id, $placename, $search_page)
    {
        return "{\t" . '"@id": ' . json_encode($search_page . '?anps_id=' . $id . '&format=json') . ',' . "\r\n" .
            "\t\t\t" . '"type": "Feature",' . "\r\n" .
            "\t\t\t" . '"properties":{' . "\r\n" .
            "\t\t\t\t" . '"title": ' . json_encode($placename) . ',' . "\r\n" .
            "\t\t\t\t" . '"ccodes": ["AU"]' . "\r\n" .
            "\t\t\t" . '},' . "\r\n";
    }

    //$source is original data source for now, link is unknown at this time
    //Will need reworking to include multiple sources pulled from the documentation and source tables
    function names($placename, $sources)
    {
        $first = true;
        $str = "\t\t\t" . '"names": [' . "\r\n" .
            "\t\t\t\t" . '{' . "\t" . '"toponym": ' . json_encode($placename) . ',' . "\r\n" .
            "\t\t\t\t\t" . '"lang":"en",' . "\r\n" .
            "\t\t\t\t\t" . '"citations": [' . "\r\n";
        foreach ($sources as $source) {
            if (!$first) $str .= ',' . "\r\n";
            $str .= "\t\t\t\t\t\t" . '{"label": ' . json_encode($source['label']) . ',' . "\r\n" .
                "\t\t\t\t\t\t" . ' "@id": ' . json_encode($source['link']) . '}';
            if ($first) $first = false;
        }
        $str .= ']' . "\r\n" .
            "\t\t\t\t" . '}' . "\r\n" .
            "\t\t\t" . '],' . "\r\n";
        return $str;
    }

    /*
        Certainty is ascertained from the flag attached to this row
        flags: 'only accurate to LGA' and 'placeholder ...' are uncertain, the rest are certain
    */
    function geometry($longitude, $latitude, $certainty)
    {
        if ($longitude == '') $longitude = 0;
        if ($latitude == '') $latitude = 0;
        return "\t\t\t" . '"geometry": {' . "\r\n" .
            "\t\t\t\t" . '"type": "GeometryCollection",' . "\r\n" .
            "\t\t\t\t" . '"geometries": [' . "\r\n" .
            "\t\t\t\t\t" . '{' . "\t" . '"type": "Point",' . "\r\n" .
            "\t\t\t\t\t\t" . '"coordinates": [' . $longitude . ',' . $latitude . '],' . "\r\n" .
            "\t\t\t\t\t\t" . '"certainty": ' . json_encode($certainty) . '' . "\r\n" .
            "\t\t\t\t\t" . '}' . "\r\n" .
            "\t\t\t\t" . ']' . "\r\n" .
            "\t\t\t" . '},' . "\r\n";
    }

    function descriptions($id, $description, $search_page)
    {
        return "\t\t\t" . '"descriptions": [' . "\r\n" .
            "\t\t\t\t" . '{' . "\t" . '"@id": ' . json_encode($search_page . '?anps_id=' . $id) . ',' . "\r\n" .
            "\t\t\t\t\t" . '"value": ' . json_encode($description) . ',' . "\r\n" .
            "\t\t\t\t\t" . '"lang": "en"' . "\r\n" .
            "\t\t\t\t" . '}' . "\r\n" .
            "\t\t\t" . ']' . "\r\n";
    }


}
