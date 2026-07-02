<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenOrder extends Model
{
    protected $table = 'kitchen_orders';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    public const STATUS_PENDING = 'pending';
    public const STATUS_READY = 'ready';
    public const STATUS_DELIVERED = 'delivered';

    protected $fillable = [
        'id', 'table_number', 'ordered_by', 'status', 'sent_at', 'ready_at', 'delivered_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(KitchenOrderLine::class, 'kitchen_order_id', 'id');
    }
}
