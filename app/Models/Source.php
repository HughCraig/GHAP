<?php

namespace TLCMap\Models;

class Source extends \Eloquent
{
    protected $table = "source";
    protected $primaryKey = "source_id";
    public $incrementing = "true";
    public $timestamps = false;
}