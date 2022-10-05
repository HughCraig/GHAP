<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Dataset extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.dataset";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'id', 'name', 'description', 'creator', 'public', 'allowanps', 'publisher', 'contact', 'citation', 'doi',
        'source_url', 'linkback', 'latitude_from', 'longitude_from', 'latitude_to', 'longitude_to', 'language', 'license', 'rights',
        'temporal_from', 'temporal_to', 'created', 'kml_style', 'kml_journey', 'recordtype_id', 'warning'
    ];

    /**
     * Define a user relationship
     * 1 user has many datasets, many datasets have many users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'tlcmap.user_dataset')->withPivot('id', 'user_id', 'dsrole', 'dataset_id', 'created_at', 'updated_at');
    }

    public function owner()
    {
        return $this->users()->where('dsrole', 'OWNER')->first()->id;
    }

    public function ownerName()
    {
        return $this->users()->where('dsrole', 'OWNER')->first()->name;
    }

    public function recordtype()
    {
        return $this->belongsTo(RecordType::class, 'recordtype_id');
    }

    public function subjectkeywords()
    {
        return $this->belongsToMany(SubjectKeyword::class, 'tlcmap.dataset_subject_keyword')->withPivot('dataset_id', 'subject_keyword_id');
    }

    /**
     * Defines a dataitem relationship
     * 1 dataset has many dataitems
     */
    public function dataitems()
    {
        return $this->hasMany(Dataitem::class);
    }

    /**
     * Defines a Collab Link relationship
     * 1 dataset has many colllablinks
     */
    public function collablinks()
    {
        return $this->hasMany(CollabLink::class);
    }

    /**
     * The collections that belong to the dataset.
     */
    public function collections()
    {
        return $this->belongsToMany('TLCMap\Models\Collection', 'tlcmap.collection_dataset', 'dataset_id', 'collection_id');
    }

    public function addData($data)
    {
        if (is_array($data)) return $this->addDataItems($data);
        return $this->addDataItem($data);
    }

    /*
        Adds a single data item
    */
    public function addDataItem($dataitem)
    {
        Dataitem::create([
            'title' => $dataitem->title,
            'latitude' => $dataitem->latitude,
            'longitude' => $dataitem->longitude
        ]);
    }

    /*
        Adds a collection of dataitems
    */
    public function addDataItems($dataitems)
    {
        foreach ($dataitems as $dataitem) {
            $this->addDataItem($dataitem);
        }
    }

    // event handler to delete this dataset's dataitems when this is deleted (untested)
    public static function boot()
    {
        parent::boot();
        self::deleting(function ($dataset) { // before delete() method call this
            $dataset->dataitems()->each(function ($dataitem) {
                $dataitem->delete();
            });
        });
    }

}
