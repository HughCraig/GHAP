<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = "tlcmap.role";

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
