<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OrderPhoto extends Model
{
    protected $fillable = [
        'order_id',
        'photo_url',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

