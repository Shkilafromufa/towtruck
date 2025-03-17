<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_location',
        'issue_type',
        'comments',
        'client_id',  
        'tow_truck_worker_id',  
        'status',
        'accident',
        'car_id',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');  
    }
    public function towTruckWorker()
    {
        return $this->belongsTo(TowTruckWorker::class, 'tow_truck_worker_id');  
    }
    public function car()
    {
        return $this->belongsTo(Car::class);
    }
    public function photos()
    {
        return $this->hasMany(OrderPhoto::class);
    }
}
