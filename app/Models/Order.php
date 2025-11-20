<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
