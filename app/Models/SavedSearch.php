<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $table = "tlcmap.saved_search";

    protected $fillable = [
        'id', 'user_id', 'query', 'count', 'name', 
        'description', 'recordtype_id', 'warning', 
        'latitude_from', 'longitude_from', 'latitude_to', 'longitude_to', 
        'temporal_from', 'temporal_to'
    ];

    public function collections()
    {
        return $this->belongsToMany('TLCMap\Models\Collection', 'tlcmap.collection_saved_search', 'saved_search_id', 'collection_id');
    }

    public function user()
    {
        return $this->belongsTo('TLCMap\Models\User');
    }

    public function getOwnerName()
    {
        return $this->user ? $this->user->name : '';
    }

    /**
     * The record type of the Saved search.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function recordtype()
    {
        return $this->belongsTo('TLCMap\Models\RecordType', 'recordtype_id');
    }
    
    /**
     * The subject keywords of the Saved search.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subjectKeywords()
    {
        return $this->belongsToMany('TLCMap\Models\SubjectKeyword', 'tlcmap.savedsearch_subjectkeyword', 'saved_search_id', 'subject_keyword_id');
    }

}
