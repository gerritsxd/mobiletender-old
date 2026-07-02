<?php

namespace App\Models;

use App\Models\UnicentaModels\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class FlashOffer extends Model
{
    protected $table = 'flash_offers';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'title', 'message', 'product_id', 'flash_price',
        'starts_at', 'ends_at', 'active', 'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
        'flash_price' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function setFlashPriceAttribute($value)
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        $this->attributes['flash_price'] = ($value === '' || $value === null) ? null : $value;
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function scopeLive($query)
    {
        $now = Carbon::now();
        return $query->where('active', true)
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>', $now);
    }

    public function isLive()
    {
        $now = Carbon::now();
        return $this->active && $this->starts_at <= $now && $this->ends_at > $now;
    }

    public function secondsLeft()
    {
        return max(0, Carbon::now()->diffInSeconds($this->ends_at, false));
    }
}
