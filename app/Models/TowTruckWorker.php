<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class TowTruckWorker extends Authenticatable
{
    use HasApiTokens, Notifiable;
    protected $table = 'tow_truck_workers';

    protected $fillable = [
        'username',
        'password',
        'email',
        'phone',
        'is_active',
        'verification_code',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];
}
