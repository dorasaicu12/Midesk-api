<?php

namespace App\Models;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class AuthByUser extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'table_users';

	protected $primaryKey = 'id'; 

    const ACTIVE = 1;

    // Rest omitted for brevity

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function Check($user)
    {
        return self::where([['email',$user['email']],['password',md5($user['password'])],['active',self::ACTIVE]])->first();
    }

    public function Permissions()
    {
        return $this->hasManyThrough(UserTypeDetail::class,UserType::class,'id','type_id','user_type_id');
    }

    public function Roles()
    {
    	return $this->hasOne(UserType::class,'id','user_type_id');
    }

    /**
     * Check if user is view_all.
     *
     * @return mixed
     */
    public function isAdmin(): bool
    {
        return $this->isRole('admin');
    }
    /**
     * Check if user is $role.
     *
     * @param string $role
     *
     * @return mixed
     */
    public function isRole(string $role): bool
    {
        return $this->Roles->pluck('type')->contains($role);
    }

    /**
     * Get all permissions of user.
     *
     * @return mixed
     */
    public static function allPermissions()
    {
        if (self::$allPermissions === null) {
            $user                 = auth()->user();
            self::$allPermissions = $user->Permissions()->get(['page','action']);
        }
        return self::$allPermissions;
    }
}