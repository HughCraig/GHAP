<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Dataitem extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.dataitem";  
    public $timestamps = true;
    public $incrementing = true;
//protected $dateFormat = 'Y-m-d H:i:sO';
    protected $fillable = [
        'id', 'dataset_id', 'recordtype_id', 'title', 'description', 'latitude', 'longitude',
        'datestart', 'dateend', 'state', 'feature_term', 'lga', 'source', 'external_url', 
        'extended_data', 'kml_style_url', 'placename'
    ];

    protected $sortable = [
        'id', 'dataset_id', 'recordtype_id', 'title', 'description', 'latitude', 'longitude',
        'datestart', 'dateend', 'state', 'feature_term', 'lga', 'source', 'external_url', 'placename'
    ];

    /**
     * Defines a dataset relationship
     * 1 dataitem belongs to 1 dataset
     */
    public function dataset() {
        return $this->belongsTo(Dataset::class);
    }

    public function recordtype() { 
        return $this->belongsTo(RecordType::class, 'recordtype_id');
    }
    // extended data should be stored as the KML version, and then turned into a table, or json or whatever as needed in output.
    // The old way of adding it direction into 'description', will cause problems with when we want it to be possible to export 
    // the data with ids so person can update them again.
    public function extDataAsHTML(){
        if (!isset($this->extended_data))
        {return null;}
        try {
        $htable = simplexml_load_string($this->extended_data, 'SimpleXMLElement', LIBXML_NOCDATA);
        $htext = "<dl class='extdata'>";
        foreach ($htable->Data as $d) {
            $htext = $htext . "<dt>" . $d->attributes()->name . "</dt><dd>" . $this->sniffUrl($d->value) . "</dd></dt>";
        }
        $htext = $htext . "</dl>";
    } catch (Exception $e) {
        return "Could not display extended data.";
    }
        return $htext;
    }
    public function extDataAsKeyValues(){
        if (!isset($this->extended_data))
        {return null;}
        $outpairs = array();
        try {
        $htable = simplexml_load_string($this->extended_data, 'SimpleXMLElement', LIBXML_NOCDATA);
        foreach ($htable->Data as $d) {
            if (empty($d->attributes()->name)) {continue;};
            $check = $d->attributes()->name;
            if ((strtolower($check) === "latitude") || (strtolower($check) === "longitude")) {
                continue;
            }
            $outpairs[$d->attributes()->name . ""] = $this->sniffUrl($d->value);
            //$htext = $htext . "<dt>" . $d->attributes()->name . "</dt><dd>" . $this->sniffUrl($d->value) . "</dd></dt>";
        }
    } catch (Exception $e) {
        return "Could not convert extended data.";
    }
        return $outpairs;
    }
    public function sniffUrl($maybeUrl){
        if (str_starts_with($maybeUrl, 'http')) {
            return "<a href='" . $maybeUrl . "'>" . htmlentities($maybeUrl) . "</a>";
        } else {
            return htmlentities($maybeUrl);
        }
    }
}
