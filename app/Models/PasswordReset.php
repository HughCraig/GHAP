<?php

namespace TLCMap\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Notifications\Notifiable;

class PasswordReset extends \Eloquent
{
    protected $connection = 'pgsql2';
    protected $table = "tlcmap.password_reset";

    protected $primaryKey = "email";
    public $incrementing = false;

    public $timestamps = true;

    protected $fillable = array('email', 'token', 'created_at');
}

