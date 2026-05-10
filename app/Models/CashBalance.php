<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashBalance extends Model
{
    protected $table = 'cash_balances';
    public $timestamps = true;

    protected $fillable = ['type', 'balance'];

    protected $casts = [
        'balance' => 'integer',
        'last_transaction_at' => 'datetime',
    ];

    const CASHIER = 'CASHIER';
    const MAIN    = 'MAIN';

    // INI YANG BENAR — PAKAI KOLOM on_behalf_of!
    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'on_behalf_of');
    }

    // Helper
    public static function cashier()
    {
        return static::where('type', self::CASHIER)->firstOrFail();
    }

    public static function main()
    {
        return static::where('type', self::MAIN)->firstOrFail();
    }
}