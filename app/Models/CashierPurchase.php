<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashierPurchase extends Model
{
    protected $table = 'cashier_purchases';

    protected $fillable = [
        'user_id',
        'category',
        'product_id',
        'quantity',
        'amount',
        'description',
        'proof_image',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'amount'   => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image) return null;
        if (filter_var($this->proof_image, FILTER_VALIDATE_URL)) return $this->proof_image;
        return asset('storage/' . $this->proof_image);
    }
}
