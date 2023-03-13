<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'pgsql2';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tlcmap.collection';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The datasets that belong to the collection.
     */
    public function datasets()
    {
        return $this->belongsToMany('TLCMap\Models\Dataset', 'tlcmap.collection_dataset', 'collection_id', 'dataset_id');
    }

    /**
     * The collection owner user.
     */
    public function ownerUser()
    {
        return $this->belongsTo('TLCMap\Models\User', 'owner');
    }

    /**
     * The subject keywords of the collection.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function subjectKeywords()
    {
        return $this->belongsToMany('TLCMap\Models\SubjectKeyword', 'tlcmap.collection_subject_keyword', 'collection_id', 'subject_keyword_id');
    }
}
