<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class RecordType extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.recordtype";
    public $incrementing = true;

    protected $fillable = [
        'id', 'type'
    ];

    public static function types()
    {
        return RecordType::all()->pluck('type');
    }

}
