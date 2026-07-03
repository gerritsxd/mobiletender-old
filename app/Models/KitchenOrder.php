<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenOrder extends Model
{
    protected $table = 'kitchen_orders';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    public const STATUS_PENDING = 'pending';      // TODO — to throw on the grill
    public const STATUS_PREPARING = 'preparing';  // DOING — being cooked
    public const STATUS_READY = 'ready';          // DONE — ready to serve
    public const STATUS_DELIVERED = 'delivered';  // gone from the board

    protected $fillable = [
        'id', 'table_number', 'ordered_by', 'status', 'sent_at', 'started_at', 'ready_at', 'delivered_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'started_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(KitchenOrderLine::class, 'kitchen_order_id', 'id');
    }
}
