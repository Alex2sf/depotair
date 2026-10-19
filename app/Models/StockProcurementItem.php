<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockProcurementItem extends Model
{
    protected $table = 'stock_procurement_items';

    protected $fillable = [
        'procurement_id',
        'product_id',
        'current_stock',
        'quantity_requested',
        'unit_cogs_requested',
        'subtotal_requested',
        'quantity_approved',
        'unit_cogs_approved',
        'subtotal_approved',
        'is_approved',
    ];

    protected $casts = [
        'current_stock'       => 'integer',
        'quantity_requested'  => 'integer',
        'unit_cogs_requested' => 'integer',
        'subtotal_requested'  => 'integer',
        'quantity_approved'   => 'integer',
        'unit_cogs_approved'  => 'integer',
        'subtotal_approved'   => 'integer',
        'is_approved'         => 'boolean',
    ];

    public function procurement(): BelongsTo
    {
        return $this->belongsTo(StockProcurement::class, 'procurement_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
