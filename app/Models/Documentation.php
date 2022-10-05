<?php

namespace TLCMap\Models;

class Documentation extends \Eloquent
{
    protected $table = "documentation";
    protected $primaryKey = "doc_id";
    public $incrementing = "true";
    public $timestamps = false;
}