<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Baris view cash_drawer_ledgers (read-only): mutasi uang fisik laci + saldo berjalan. */
class CashDrawerLedger extends Model
{
    protected $table = 'cash_drawer_ledgers';
    public $timestamps = false;
    public $incrementing = false;

    protected $casts = [
        'transaction_date' => 'datetime',
        'amount_in' => 'integer',
        'amount_out' => 'integer',
        'running_balance' => 'integer',
    ];

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
