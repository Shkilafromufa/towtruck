<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'user_location',
        'issue_type',
        'vehicle_type',
        'comments',
    ];

    public function photos()
    {
        return $this->hasMany(OrderPhoto::class);
    }
}
