<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class SavedSearch extends Model
{
    protected $table = "tlcmap.saved_search";

    protected $fillable = [
        'id', 'user_id', 'query', 'count', 'name'
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
}