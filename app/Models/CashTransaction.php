<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $table = 'cash_transactions';

    protected $fillable = [
        'type',
        'amount',
        'description',
        'proof_image',
        'recorded_by',
        'on_behalf_of',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

    protected $appends = [
        'proof_image_url',
    ];

    public function getProofImageUrlAttribute(): ?string
    {
        if (!$this->proof_image) return null;
        if (filter_var($this->proof_image, FILTER_VALIDATE_URL)) return $this->proof_image;

        $path = ltrim($this->proof_image, '/');

        // 1. Cek langsung di direktori public/ (jika upload fallback langsung ke public)
        if (file_exists(public_path($path))) {
            return asset($path);
        }

        // 2. Cek di direktori public/storage/ (jika symlink atau subfolder storage ada di public)
        if (file_exists(public_path('storage/' . $path))) {
            return asset('storage/' . $path);
        }

        // 3. Cek disk public via Storage facade
        try {
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        } catch (\Throwable $e) {}

        // 4. Default asset storage
        return asset('storage/' . $path);
    }

    const TYPE_EXPENSE = 'EXPENSE';
    const TYPE_DEPOSIT = 'DEPOSIT';

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function onBehalfOf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'on_behalf_of');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}