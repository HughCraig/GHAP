<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;
use TLCMap\ViewConfig\FeatureCollectionConfig;
use TLCMap\ViewConfig\GhapConfig;

class Collection extends Model
{
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

    /**
     * The saved searches of the collection.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function savedSearches()
    {
        return $this->belongsToMany('TLCMap\Models\SavedSearch', 'tlcmap.collection_saved_search', 'collection_id', 'saved_search_id');
    }

    /**
     * Get all user-editable metadata for this collection.
     *
     * @return array
     *   An associative array of all metadata fields for this collection.
     */
    public function getMetadata()
    {
        $metadata = array(
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'subject_keywords' => $this->subjectKeywords->pluck('keyword')->toArray(),
            'warning' => $this->warning,
            'linkback' => $this->linkback,
            'owner' => $this->owner,
            'creator' => $this->creator,
            'publisher' => $this->publisher,
            'contact' => $this->contact,
            'doi' => $this->doi,
            'source_url' => $this->source_url,
            'license' => $this->license,
            'rights' => $this->rights,
            'citation' => $this->citation,
            'language' => $this->language,
            'latitude_from' => $this->latitude_from,
            'longitude_from' => $this->longitude_from,
            'latitude_to' => $this->latitude_to,
            'longitude_to' => $this->longitude_to,
            'temporal_from' => $this->temporal_from,
            'temporal_to' => $this->temporal_to,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        );

        return array_filter($metadata, function ($value) {
            if (is_array($value)) return !empty($value);
            return $value !== null && $value !== '';
        });
    }


    /**
     * Generate the GeoJSON when visiting a private collection or non-exist collection.
     * show warning message at info block
     */
    public static function getRestrictedCollectionGeoJSON()
    {

        $featureCollectionConfig = new FeatureCollectionConfig();
        $featureCollectionConfig->setInfoContent(GhapConfig::createRestrictedDatasetInfoBlockContent());
        $allfeatures = array(
            'metadata' => [
                'warnnig' => 'This map either does not exist or has been set to "private" and therefore cannot be displayed.'
            ],
            'display' => $featureCollectionConfig->toArray(),
            'datasets' => [],
        );

        return json_encode($allfeatures, JSON_PRETTY_PRINT);
    }
}
