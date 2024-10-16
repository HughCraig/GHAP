<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Text extends Model
{
    protected $table = "tlcmap.text";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'id', 'name', 'description', 'creator',  'publisher', 'contact', 'citation', 'doi',
        'source_url', 'linkback', 'language', 'license', 'rights',
        'temporal_from', 'temporal_to', 'created','texttype_id', 'warning' , 'image_path' , 'content'
    ];

    /**
     * Define a user relationship
     * 1 user has many text, many text have many users
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'tlcmap.user_text')->withPivot('id', 'user_id', 'dsrole', 'text_id', 'created_at', 'updated_at');
    }

    public function owner()
    {
        return $this->users()->where('dsrole', 'OWNER')->first()->id;
    }

    public function ownerName()
    {
        return $this->users()->where('dsrole', 'OWNER')->first()->name;
    }

    /**
     * Get  Text Type for this text.
     */
    public function texttype()
    {
        return $this->belongsTo(TextType::class, 'texttype_id');
    }

    public function subjectKeywords()
    {
        return $this->belongsToMany(SubjectKeyword::class, 'tlcmap.text_subject_keyword')->withPivot('text_id', 'subject_keyword_id');
    }

    /**
     * Get all datasets created from this text.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function datasets()
    {
        return $this->hasMany(Dataset::class, 'from_text_id');
    }

}
