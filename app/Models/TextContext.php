<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class TextContext extends Model
{
    protected $table = "tlcmap.text_contexts";

    public $timestamps = false; 

    protected $fillable = [
        'dataitem_uid', 'text_id', 'start_index', 'end_index', 
        'sentence_start_index', 'sentence_end_index', 'line_index',
        'line_word_start_index', 'line_word_end_index'
    ];

    /**
     * Define relationship to the Text model.
     * Each TextContext belongs to a Text.
     */
    public function text()
    {
        return $this->belongsTo(Text::class, 'text_id');
    }

    public static function getContentByDataitemUid($dataitem_uid)
    {
        return self::where('dataitem_uid', $dataitem_uid)->get();
    }

    public static function getAllByDataitemUid($dataitem_uid)
    {
        return self::where('dataitem_uid', $dataitem_uid)->get();
    }
}
