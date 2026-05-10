<?php

namespace App\Models;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

use App\Enums\MovementReason;

class InventoryMovement extends Model
{
    use HasFactory;
    protected $table = 'inventory_movements';

    protected $fillable = [
        'product_id',
        'order_id',
        'user_id',
        'quantity_before',
        'quantity_change',
        'quantity_after',
        'reason',
        'notes',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
        'reason' => MovementReason::class,
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // /**
    //  * Mendapatkan Inventory yang terkait dengan pergerakan ini.
    //  */
    // public function inventory(): BelongsTo
    // {
    //     return $this->belongsTo(Inventory::class);
    // }

    /**
     * Mendapatkan Order yang (mungkin) menyebabkan pergerakan ini.
     * Relasi ini bisa null.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Mendapatkan User (staf) yang mencatat pergerakan ini.
     */
    public function user(): BelongsTo
    {
        // Asumsi Anda memiliki model App\Models\User
        return $this->belongsTo(User::class);
    }

    // app/Models/InventoryMovement.php

protected static function booted()
{
    // VALIDASI ANTI-BOHONG (wajib ada!)
    static::saving(function ($movement) {
        $expected = $movement->quantity_before + $movement->quantity_change;
        if ($movement->quantity_after !== $expected) {
            throw ValidationException::withMessages([
                'quantity_after' => "BOHONG TERDETEKSI! Harusnya {$expected}, bukan {$movement->quantity_after}",
            ]);
        }
    });

    // SYNC STOK KE TABEL INVENTORIES SECARA REAL-TIME
    static::created(function ($movement) {
        $inventory = \App\Models\Inventory::firstOrNew(['product_id' => $movement->product_id]);
        $inventory->quantity = $movement->quantity_after;
        $inventory->saveQuietly(); // tanpa trigger event lagi biar ga loop
    });

    static::updated(function ($movement) {
        $inventory = \App\Models\Inventory::firstOrNew(['product_id' => $movement->product_id]);
        $inventory->quantity = $movement->quantity_after;
        $inventory->saveQuietly();
    });

    static::deleted(function ($movement) {
        // Optional: kalau movement dihapus, rollback stok?
        // Gw saranin JANGAN bolehin delete movement pernah!
    });
}
}
