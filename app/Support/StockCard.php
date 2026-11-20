<?php

namespace App\Support;

use App\Models\Product;
use App\Models\StockOpname;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Kartu stok per produk untuk satu periode, dihitung dari inventory_movements:
 * stok awal + restock + retur - jual - rusak +/- penyesuaian = stok akhir,
 * lalu dibandingkan dengan opname terakhir di periode itu.
 */
class StockCard
{
    /** @return array<int, array<string, mixed>> */
    public static function rows(CarbonInterface $from, CarbonInterface $to): array
    {
        $start = $from->copy()->startOfDay();
        $end = $to->copy()->endOfDay();

        $sums = DB::table('inventory_movements')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('product_id', 'reason')
            ->select('product_id', 'reason', DB::raw('SUM(quantity_change) AS qty'))
            ->get()
            ->groupBy('product_id');

        $rows = [];
        foreach (Product::with('inventory')->orderBy('name')->get() as $product) {
            $byReason = collect($sums->get($product->id, []))->pluck('qty', 'reason')->map(fn ($q) => (int) $q);

            $opening = self::openingStock($product->id, $start, (int) ($product->inventory->quantity ?? 0));
            $net = (int) $byReason->sum();
            $closing = $opening + $net;

            $opname = StockOpname::where('product_id', $product->id)
                ->whereBetween('counted_at', [$start, $end])
                ->latest('counted_at')->latest('id')
                ->first();

            $rows[] = [
                'product_id' => $product->id,
                'product' => $product->name,
                'unit' => $product->unit,
                'opening' => $opening,
                'restock' => $byReason->get('RESTOCK', 0),
                'return' => $byReason->get('RETURN', 0),
                'sale' => abs($byReason->get('SALE', 0)),
                'damage' => abs($byReason->get('DAMAGE', 0)),
                'adjustment' => $byReason->get('ADJUSTMENT', 0),
                'closing' => $closing,
                'current' => (int) ($product->inventory->quantity ?? 0),
                'opname_at' => $opname?->counted_at,
                'opname_system' => $opname?->system_quantity,
                'opname_real' => $opname?->real_quantity,
                'opname_difference' => $opname?->difference,
            ];
        }

        return $rows;
    }

    /** Stok di awal periode: posisi setelah gerakan terakhir sebelum periode, atau sebelum gerakan pertama di periode. */
    private static function openingStock(int $productId, CarbonInterface $start, int $current): int
    {
        $before = DB::table('inventory_movements')
            ->where('product_id', $productId)
            ->where('created_at', '<', $start)
            ->orderByDesc('created_at')->orderByDesc('id')
            ->value('quantity_after');
        if ($before !== null) {
            return (int) $before;
        }

        $firstInPeriod = DB::table('inventory_movements')
            ->where('product_id', $productId)
            ->where('created_at', '>=', $start)
            ->orderBy('created_at')->orderBy('id')
            ->value('quantity_before');

        return $firstInPeriod !== null ? (int) $firstInPeriod : $current;
    }
}
