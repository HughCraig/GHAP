<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class RecordType extends Model
{
    protected $table = "tlcmap.recordtype";
    public $incrementing = true;

    protected $fillable = [
        'id', 'type'
    ];

    public static function types()
    {
        return RecordType::all()->pluck('type');
    }

    //Get recordtype type by id
    // Return "Other" if not found
    public static function getTypeById($id)
    {
        $record = RecordType::find($id);
        return $record ? $record->type : "Other";
    }

    //Get recordtype id by type
    // Return 1 (Others) if not found
    public static function getIdByType($type)
    {
        $record = RecordType::where('type', trim($type))->first();
        return $record ? $record->id : 1;
    }

}
