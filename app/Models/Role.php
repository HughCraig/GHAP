<?php

namespace TLCMap\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.role";

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
