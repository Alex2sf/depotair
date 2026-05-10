<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // TAMBAH INI!

class OrderProduct extends Pivot
{
    protected $table = 'order_product';
    public $incrementing = true;
    protected $guarded = []; // Allow mass assignment for Repeater created records

    protected static function booted()
    {
        static::saving(function ($pivot) {
            $pivot->subtotal = $pivot->quantity * $pivot->price_at_sale;
        });
    }

    public const PIVOT_COLUMNS = [
        'product_name',
        'quantity',
        'price_at_sale',
        'cogs_at_sale',
        'subtotal',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'price_at_sale' => 'integer',
        'cogs_at_sale'  => 'integer',
        'subtotal'      => 'integer',
    ];

    // INI YANG WAJIB DI-UNCOMMENT!
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}