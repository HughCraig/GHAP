<?php

namespace TLCMap\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use TLCMap\Models\Route;
use function foo\func;

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
        'extended_data', 'kml_style_url', 'placename', 'original_id', 'parish', 'image_path', 'dataset_order',
        'route_id', 'quantity'

    ];

    /**
     * Defines a dataset relationship
     * 1 dataitem belongs to 1 dataset
     */
    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }

    /**
     * Defines a route relationship
     * 1 dataitem belongs to 1 route only
     */
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id');
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
                $items[] = '<Data name="' . trim($key) . '"><value><![CDATA[' . trim($value) . ']]></value></Data>';
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
            if (isset($extDataXML->Data)) {
                foreach ($extDataXML->Data as $item) {
                    $extData[(string) $item->attributes()->name] = (string) $item->value;
                }
            }
        } catch (\Exception $e) {
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
     * Scope query to include route information and stop index for searched dataitems.
     *
     * This scope performs the following:
     * 1. Creates a subquery to get route information and calculate stop index for each dataitem.
     * 2. Left joins this subquery with the main dataitem query.
     * 3. Adds additional columns from the route information to the select statement.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithStopIdxForSearchedDataitems($query)
    {
        return $query->leftJoinSub(
            function ($subQuery) use ($query) {
                $subQuery->select(
                    'tlcmap.route_order.dataitem_id',
                    'tlcmap.route.title as route_title',
                    'tlcmap.route.description as route_description',
                    'tlcmap.route.size as route_size',
                    // Calculate the stop index for each dataitem within its route
                    DB::raw('ROW_NUMBER() OVER (PARTITION BY tlcmap.route_order.route_id ORDER BY tlcmap.route_order.position) as stop_idx')
                )
                    ->from('tlcmap.route_order')
                    ->join('tlcmap.route', 'tlcmap.route_order.route_id', '=', 'tlcmap.route.id')
                    ->joinSub($query->select('id', 'route_id'), 'filtered_dataitems', function ($join) {
                        $join->on('tlcmap.route_order.dataitem_id', '=', 'filtered_dataitems.id');
                    });
            },
            'route_info',
            'tlcmap.dataitem.id',
            '=',
            'route_info.dataitem_id'
        )->addSelect(
            'tlcmap.dataitem.*',
            'route_info.route_title',
            'route_info.route_description',
            'route_info.route_size',
            'route_info.stop_idx'
        );
    }

    public function getCurrentStopIdx()
    {
        $route = $this->route;

        if (!$route) {
            return null;
        }

        $stopIndices = $route->allStopIndices();
        return $stopIndices[(string)$this->id] ?? null;
    }

    /**
     * Get the details of the Route associated with the current Dataitem instance.
     * If no Route is associated, it returns null.
     *
     * @return array|null An associative array containing the details of the associated Route, or null if no Route is associated.
     */
    public function currentRouteDetails()
    {
        $route = $this->route;

        if ($route) {
            $details = $route->currentDetails();
            $stopIndices = $details['allStopIndices'];
            $details['currentStopIdx'] = $stopIndices[$this->id] ?? null;
            $details['allStopIndices'] = array_values($stopIndices);

            return $details;
        }

        return null;
    }

    /**
     * Check if the dataset that current dataitem is associated
     * with has other routes
     *
     * @return bool
     */
    public function hasOtherRoutes(): bool
    {
        $tjcsNum = count($this->dataset->routes);
        $has_route = $this->route_id;

        // Return true if the Dataitem is associated with a route and
        // there are more than one route in the dataset,
        // or if the Dataitem is not associated with a route but there are routes in the dataset
        return ($tjcsNum > ($has_route ? 1 : 0));
    }

    /**
     * Get all other Routes associated with the same Dataset as the current Dataitem instance,
     * excluding the Route associated with the current Dataitem instance.
     * If the current Dataitem instance is not associated with any Route, it returns all Routes associated with the Dataset.
     *
     * @return Collection|null A collection of Route instances.
     */
    public function allOtherRoutes()
    {
        $dataset = $this->dataset;
        $currentRouteId = $this->route_id;

        if ($dataset) {
            $routes = $dataset->routes;

            if ($currentRouteId) {
                return $routes->filter(function ($route) use ($currentRouteId) {
                    return $route->id !== $currentRouteId;
                });
            } else {
                return $routes;
            }
        }

        return null;
    }

    /**
     * Get the details of all other Routes associated with the same Dataset as the current Dataitem instance,
     * excluding the Route associated with the current Dataitem.
     *
     * @return array An array of associative arrays, each containing the details of a Route.
     */
    public function allOtherRoutesDetails()
    {
        $otherRoutes = $this->allOtherRoutes();

        $otherRoutesDetails = [];
        foreach ($otherRoutes as $route) {
            $otherRoutesDetails[] = $route->currentDetails();
        }

        return $otherRoutesDetails;
    }

    /**
     * Update route_id for multiple dataitems
     *
     * @param int $routeId
     * @param array $dataitemIds
     */
    public static function updateRouteIdBatch($routeId, $dataitemIds)
    {
        return self::whereIn('id', $dataitemIds)
            ->update(['route_id' => $routeId]);
    }

    /**
     * Batch update to set route_id to null for multiple dataitems
     *
     * @param array $dataitemIds
     * @return int The number of affected rows
     */
    public static function clearRouteIdBatch(array $dataitemIds)
    {
        return self::whereIn('id', $dataitemIds)->update(['route_id' => null]);
    }
}
