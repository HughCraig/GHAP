<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectKeyword extends Model
{
    protected $table = "tlcmap.subject_keyword";
    public $timestamps = false;
    public $incrementing = true;

    protected $fillable = [
        'id', 'keyword'
    ];

    public function datasets()
    {
        return $this->belongsToMany(DataSet::class, 'dataset_subject_keyword')->withPivot('dataset_id', 'subject_keyword_id');
    }

    /**
     * The collections which have the subject keywords.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function collections()
    {
        return $this->belongsToMany('TLCMap\Models\Collection', 'tlcmap.collection_subject_keyword', 'subject_keyword_id', 'collection_id');
    }

    /**
     * The saved searches which have the subject keyword.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function savedSearches()
    {
        return $this->belongsToMany('TLCMap\Models\SavedSearch', 'tlcmap.savedsearch_subjectkeyword', 'subject_keyword_id', 'saved_search_id');
    }

    /**
    * The texts which have the subject keywords.
    *
    * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
    */
    public function texts()
    {
        return $this->belongsToMany('TLCMap\Models\Text', 'tlcmap.text_subject_keyword', 'subject_keyword_id', 'text_id');
    }
}
