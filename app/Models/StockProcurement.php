<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockProcurement extends Model
{
    protected $table = 'stock_procurements';

    protected $fillable = [
        'procurement_number',
        'requested_by',
        'approved_by',
        'total_estimated_amount',
        'total_approved_amount',
        'status',
        'notes',
        'owner_notes',
        'proof_image',
        'approved_at',
        'received_at',
        'received_by',
    ];

    protected $casts = [
        'total_estimated_amount' => 'integer',
        'total_approved_amount'  => 'integer',
        'approved_at'            => 'datetime',
        'received_at'            => 'datetime',
    ];

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockProcurementItem::class, 'procurement_id');
    }

    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image) return null;
        if (filter_var($this->proof_image, FILTER_VALIDATE_URL)) return $this->proof_image;
        return asset('storage/' . $this->proof_image);
    }
}
