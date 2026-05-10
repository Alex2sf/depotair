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
        'recorded_by',
        'on_behalf_of',
        'order_id',
    ];

    protected $casts = [
        'amount' => 'integer',
    ];

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