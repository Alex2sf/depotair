<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;        // TAMBAH INI BRO!
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentType;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id', 'staff_id', 'courier_id', 'status', 'order_type', 'payment_type',
        'delivery_address', 'address_link','delivery_scheduled_at', 'latitude', 'longitude',
        'subtotal', 'delivery_fee', 'additional_fee', 'total_amount',
        'delivery_time', 'completed_time', 'notes', 'ready_time',
        'prepared_target_at', 'ready_target_at', 'delivery_target_at', 'completed_target_at',
    ];

    protected $casts = [
        'subtotal'              => 'integer',
        'delivery_fee'          => 'integer',
        'additional_fee'        => 'integer',
        'total_amount'          => 'integer',
        'latitude'              => 'float',
        'longitude'             => 'float',
        'delivery_time'         => 'datetime',
        'completed_time'        => 'datetime',
        'delivery_scheduled_at' => 'datetime',
        'ready_time'            => 'datetime',
        'prepared_target_at'    => 'datetime',
        'ready_target_at'       => 'datetime',
        'delivery_target_at'    => 'datetime',
        'completed_target_at'   => 'datetime',
        'status'                => OrderStatus::class,
        'order_type'            => OrderType::class,
        'payment_type'          => PaymentType::class,
    ];

    // RELASI
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->using(OrderProduct::class)
            ->withPivot(...OrderProduct::PIVOT_COLUMNS)
            ->withTimestamps();
    }

    public function orderProducts(): HasMany
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    // INI YANG BENAR — CUMA SATU KALI!
    public function deliveryProof(): HasOne
    {
        return $this->hasOne(OrderDeliveryProof::class);
    }

    public function hasDeliveryProof(): bool
    {
        return $this->deliveryProof()->exists();
    }

    // AUTO GENERATE ORDER NUMBER
    protected static function boot()
    {
        parent::boot();

        static::created(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD' . now()->format('Ymd') . str_pad($order->id, 6, '0', STR_PAD_LEFT);
                $order->saveQuietly();
            }
        });
    }
}