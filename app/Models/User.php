<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;
    public $timestamps = false;
    protected $table = 'users';

    protected $fillable = [
        'username', 'password', 'role', 'phone', 'email', 'is_active', 'verification_code',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
    public function hasRole($role)
    {
        return $this->role === $role;
    }
    public function getJWTCustomClaims()
    {
        return ['role' => $this->role, // Добавление роли пользователя в токен
    ];
    }
}
