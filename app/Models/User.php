<?php

namespace TLCMap\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Auth\Authenticatable as AuthenticableTrait;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\MustVerifyEmail;

class User extends \Eloquent implements Authenticatable, CanResetPasswordContract, MustVerifyEmailContract
{
    use AuthenticableTrait, CanResetPassword;
    use MustVerifyEmail, Notifiable;
    protected $table = "tlcmap.user";

    protected $primaryKey = "id";
    public $incrementing = true; //change to true as we use id instead of email as PK now

    public $timestamps = true;

    protected $fillable = array('id', 'name', 'email', 'email_verfied_at', 'password', 'remember_token',
        'updated_at', 'created_at', 'is_active');

    /**
     * Define a many to 1 relationship (users have 1 role, 1 role has many users)
     */
    public function roles()
    {
        // postgres seems to require table names to be fully qualified with db name, so trying to specify the joining table here.
        return $this->belongsToMany(Role::class, 'tlcmap.role_user')->withPivot('id', 'role_id', 'user_id');
    }

    /**
     * Define a dataset relationship
     * 1 user has many datasets, many datasets have many users
     */
    public function datasets()
    {
        return $this->belongsToMany(Dataset::class, 'tlcmap.user_dataset')->withPivot('id', 'user_id', 'dsrole', 'dataset_id', 'created_at', 'updated_at');
    }

    /**
     * User owned collections.
     */
    public function collections()
    {
        return $this->hasMany('TLCMap\Models\Collection', 'owner');
    }

     /**
     * Define a text relationship
     * 1 user has many texts, many texts have many users
     */
    public function texts()
    {
        return $this->belongsToMany(Text::class, 'tlcmap.user_text')->withPivot('id', 'user_id', 'dsrole', 'text_id', 'created_at', 'updated_at');
    }

    /**
     * Define this user's datasetrole in the dataset
     * Hard coded datasetroles are: OWNER, COLLABORATOR, VIEWER
     */
    public function addDsrole($datasetid, $datasetrole)
    {
        $this->datasets()->attach($datasetid, ['datasetrole' => $datasetrole]);
    }

    /**
     * Get this user's datasetrole for the dataset with this id
     */
    public function getDsrole($datasetid)
    {
        $this->datasets()->find($datasetid)->datasetrole;
    }


    /**
     * Check if the User has any of the defined roles
     * @param string|array $roles
     */
    public function authorizeRoles($roles)
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles) ||
                abort(403, 'Error 403 - Forbidden.');
        }
        return $this->hasRole($roles) ||
            abort(403, 'Error 403 - Forbidden.');
    }

    /**
     * Check multiple roles
     * @param array $roles
     */
    public function hasAnyRole($roles)
    {
        return null !== $this->roles()->whereIn('name', $roles)->first();
    }

    /**
     * Check one role
     * @param string $role
     */
    public function hasRole($role)
    {
        return null !== $this->roles()->where('name', $role)->first();
    }

    public function isActive()
    {
        return $this->is_active;
    }

    public function hasConfirmed()
    {
        return $this->email_verfied_at === null ? false : true;
    }

    /**
     * Check if the user is locked
     * @return bool
     */
    public function isLocked()
    {
        return $this->hasRole('LOCKED');
    }

}

