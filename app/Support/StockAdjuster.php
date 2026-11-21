<?php

namespace App\Support;

use App\Enums\MovementReason;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya cara mengubah angka stok di luar transaksi jual/beli: selalu lewat riwayat gerakan stok,
 * supaya Kartu Stok tetap bisa merunut angka stok sistem.
 */
class StockAdjuster
{
    /** Set stok produk ke angka target. Mengembalikan selisih yang dicatat (0 = tidak ada perubahan). */
    public static function setTo(int $productId, int $target, ?int $userId, string $notes, MovementReason $reason = MovementReason::ADJUSTMENT): int
    {
        if ($target < 0) {
            throw new \DomainException('Stok tidak boleh minus.');
        }

        return DB::transaction(function () use ($productId, $target, $userId, $notes, $reason) {
            $inventory = Inventory::firstOrCreate(['product_id' => $productId], ['quantity' => 0, 'low_stock_threshold' => 10]);
            $inventory = Inventory::whereKey($inventory->id)->lockForUpdate()->first();

            $before = (int) $inventory->quantity;
            $change = $target - $before;
            if ($change === 0) {
                return 0;
            }

            // Event created di InventoryMovement menyamakan inventories.quantity dengan quantity_after
            InventoryMovement::create([
                'product_id'      => $productId,
                'order_id'        => null,
                'user_id'         => $userId,
                'quantity_before' => $before,
                'quantity_change' => $change,
                'quantity_after'  => $target,
                'reason'          => $reason,
                'notes'           => $notes,
            ]);

            return $change;
        });
    }
}
