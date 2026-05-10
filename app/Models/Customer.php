<?php

namespace App\Models;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;
    protected $table = 'customers';

    // Kolom 'location' (POINT) mungkin memerlukan
    // library tambahan (seperti laravel-postgis) dan Custom Cast.
    protected $fillable = [
        'name',
        'phone_number',
        'address',

        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Mendapatkan semua pesanan (orders) yang dimiliki oleh Customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
