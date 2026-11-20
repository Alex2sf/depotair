<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpname extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'system_quantity',
        'real_quantity',
        'difference',
        'notes',
        'counted_at',
    ];

    protected $casts = [
        'system_quantity' => 'integer',
        'real_quantity' => 'integer',
        'difference' => 'integer',
        'counted_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
