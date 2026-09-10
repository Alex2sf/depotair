<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $table = 'cashier_shifts';

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'starting_cash',
        'cash_sales',
        'cash_expenses',
        'cash_deposited',
        'expected_cash',
        'actual_cash',
        'difference',
        'notes',
        'status',
    ];

    protected $casts = [
        'start_time'     => 'datetime',
        'end_time'       => 'datetime',
        'starting_cash'  => 'integer',
        'cash_sales'     => 'integer',
        'cash_expenses'  => 'integer',
        'cash_deposited' => 'integer',
        'expected_cash'  => 'integer',
        'actual_cash'    => 'integer',
        'difference'     => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
