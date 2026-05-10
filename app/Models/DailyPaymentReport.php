<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyPaymentReport extends Model
{
    protected $table = 'daily_payment_reports';
    public $timestamps = false; // View doesn't have updated_at
    protected $primaryKey = 'id'; // MIN(id) from view

    protected $casts = [
        'date' => 'date',
        'tunai_total' => 'integer',
        'qris_total' => 'integer',
        'transfer_total' => 'integer',
        'corporate_total' => 'integer',
        'grand_total' => 'integer',
        // created_at removed from view
    ];
}
