<?php

namespace App\Models;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Order;
use App\Models\InventoryMovement;
use App\Enums\MovementReason;  // Import dari Enums (udah bener)

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventories';

    // TAMBAHIN real_quantity dan last_opname_at KE SINI!!!
    protected $fillable = [
        'product_id',
        'quantity',
        'real_quantity',        // tambah ini
        'last_opname_at',       // tambah ini
        'low_stock_threshold',
    ];

    protected $casts = [
        'quantity'            => 'integer',
        'real_quantity'       => 'integer',     // penting banget!
        'low_stock_threshold' => 'integer',
        'last_opname_at'      => 'datetime',    // atau 'timestamp'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Method deductStock (update: ga pake user_id, set null aja biar sesuai permintaan lo)
    public function deductStock(int $quantity, Order $order): void
    {
        if ($this->quantity < $quantity) {
            throw new \Exception("Stok tidak cukup untuk produk ini.");
        }

        $before = $this->quantity;
        $this->quantity -= $quantity;
        $this->save();

        // Log ke InventoryMovement (tanpa user_id, atau set null)
        InventoryMovement::create([
            'product_id' => $this->product_id,
            'order_id' => $order->id,
            'user_id' => null,  // <-- Ini sesuai request lo: ga pake user_id
            'quantity_before' => $before,
            'quantity_change' => -$quantity,
            'quantity_after' => $this->quantity,
            'reason' => MovementReason::SALE,
            'notes' => "Stok dikurangi untuk order #{$order->order_number}",
        ]);
    }
}
