<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenOrderLine extends Model
{
    protected $table = 'kitchen_order_lines';
    public $timestamps = false;

    protected $fillable = [
        'kitchen_order_id', 'product_id', 'product_name', 'price', 'printto',
    ];

    protected $casts = [
        'price' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(KitchenOrder::class, 'kitchen_order_id', 'id');
    }
}
