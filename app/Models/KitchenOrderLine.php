<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenOrderLine extends Model
{
    protected $table = 'kitchen_order_lines';
    public $timestamps = false;

    public const STATUS_PENDING = 'pending';      // to cook
    public const STATUS_PREPARING = 'preparing';  // cooking
    public const STATUS_READY = 'ready';          // ready to run
    public const STATUS_DELIVERED = 'delivered';  // gone

    protected $fillable = [
        'kitchen_order_id', 'product_id', 'product_name', 'quantity', 'price', 'printto',
        'status', 'started_at', 'ready_at', 'delivered_at',
    ];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'integer',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(KitchenOrder::class, 'kitchen_order_id', 'id');
    }
}
