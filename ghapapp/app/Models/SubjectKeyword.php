<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class SubjectKeyword extends Model
{
    protected $connection = 'pgsql2';
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
        return $this->belongsToMany('TLCMap\Models\Collection', 'collection_subject_keyword', 'subject_keyword_id', 'collection_id');
    }
}
