<?php
// app/Models/OrderDeliveryProof.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderDeliveryProof extends Model
{
    protected $table = 'order_delivery_proofs';

    protected $fillable = [
        'order_id',
        'uploaded_by',
        'image_url',
        'notes',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}