<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Dsrole extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.dsrole";

    protected $fillable = [ 
        'id', 'name', 'description'
    ];
}
