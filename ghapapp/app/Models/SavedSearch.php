<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.saved_search";

    protected $fillable = [ 
        'id', 'user_id', 'query', 'count', 'name'
    ];
}
