<?php

namespace TLCMap\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Dataitem extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.dataitem";
    public $timestamps = true;
    public $incrementing = true;

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
    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    public function recordtype()
    {
        return $this->belongsTo(RecordType::class, 'recordtype_id');
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
                $extData[(string) $item->attributes()->name] = $item->value;
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
}
