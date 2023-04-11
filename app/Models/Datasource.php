<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Datasource extends Model
{
    protected $table = "tlcmap.datasource";

    /**
     * Dataitems belong to the datasource.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dataitems()
    {
        return $this->hasMany(Dataitem::class, 'datasource_id');
    }

    /**
     * Get the ANPS datasource.
     *
     * This is for legacy support of ANPS source. It uses the search parameter name
     * to find the datasource as the name is unlikely to change.
     *
     * @return Datasource
     */
    public static function anps()
    {
        return self::where('search_param_name', 'searchausgaz')->first();
    }
}
