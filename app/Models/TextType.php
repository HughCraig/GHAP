<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class TextType extends Model
{
    protected $table = "tlcmap.texttype";
    public $incrementing = true;

    protected $fillable = [
        'id', 'type'
    ];

    public static function types()
    {
        return TextType::all()->pluck('type');
    }

    /**
     * Get a mapping of record types with IDs as keys and type as values.
     *
     * @return \Illuminate\Support\Collection Associative array of record types indexed by their IDs.
     */
    public static function getIdTypeMap()
    {
        return TextType::all()->pluck('type', 'id');
    }
}
