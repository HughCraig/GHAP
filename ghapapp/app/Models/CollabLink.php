<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class CollabLink extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.collablink";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'dataset_id', 'link', 'dsrole'
    ];

    /**
     * Define a dataset relationship
     * 1 dataset has many collab links, 1 collab link has 1 dataset
     */
    public function dataset()
    {
        return $this->belongsTo(Dataset::class);
    }
}
