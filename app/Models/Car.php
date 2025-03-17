<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model
{
    // Пример атрибутов модели Car
    protected $fillable = ['model', 'year', 'reg_number', 'weight'];

    // Связь с заказами
    public function orders()
    {
        return $this->hasMany(Order::class); // Связь с заказами
    }
}
