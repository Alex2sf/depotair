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

    /**
     * Ringkasan per hari dalam 1 bulan penuh (tanggal 1 s/d akhir bulan / hari ini),
     * memakai 2 query agregat (anti N+1) agar ringan meski produk banyak.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function monthlyDailySummary(int $year, int $month): array
    {
        $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $todayEnd = now()->endOfDay();
        $effectiveEnd = $monthEnd->gt($todayEnd) ? $todayEnd : $monthEnd;

        if ($effectiveEnd->lt($monthStart)) {
            return [];
        }

        // 1. Agregat mutasi stok per tanggal & reason
        $movementRows = DB::table('inventory_movements')
            ->whereBetween('created_at', [$monthStart, $effectiveEnd])
            ->selectRaw('DATE(created_at) as dt, reason, SUM(quantity_change) as qty, COUNT(DISTINCT product_id) as prod_cnt')
            ->groupByRaw('DATE(created_at), reason')
            ->get();

        $movByDate = [];
        foreach ($movementRows as $r) {
            $dt = (string) $r->dt;
            $movByDate[$dt][$r->reason] = (int) $r->qty;
            $movByDate[$dt]['_moved'] = max($movByDate[$dt]['_moved'] ?? 0, (int) $r->prod_cnt);
        }

        // Jumlah produk unik yang bergerak per hari
        $distinctMoved = DB::table('inventory_movements')
            ->whereBetween('created_at', [$monthStart, $effectiveEnd])
            ->selectRaw('DATE(created_at) as dt, COUNT(DISTINCT product_id) as prod_cnt')
            ->groupByRaw('DATE(created_at)')
            ->pluck('prod_cnt', 'dt');

        // 2. Opname per tanggal: ambil opname terakhir per (tanggal, product_id)
        $opnameRows = DB::table('stock_opnames')
            ->whereBetween('counted_at', [$monthStart, $effectiveEnd])
            ->orderBy('counted_at')
            ->orderBy('id')
            ->get(['product_id', 'counted_at', 'difference']);

        $latestOpnameByDateProd = [];
        $opnameStatsByDate = [];
        foreach ($opnameRows as $op) {
            $dt = substr((string) $op->counted_at, 0, 10);
            $key = $dt . ':' . $op->product_id;
            $diff = (int) $op->difference;

            if (!isset($opnameStatsByDate[$dt])) {
                $opnameStatsByDate[$dt] = ['count' => 0, 'issues' => 0];
            }

            if (!array_key_exists($key, $latestOpnameByDateProd)) {
                $opnameStatsByDate[$dt]['count']++;
                if ($diff !== 0) {
                    $opnameStatsByDate[$dt]['issues']++;
                }
            } else {
                $prevWasIssue = $latestOpnameByDateProd[$key] !== 0;
                $nowIsIssue = $diff !== 0;
                if (!$prevWasIssue && $nowIsIssue) {
                    $opnameStatsByDate[$dt]['issues']++;
                } elseif ($prevWasIssue && !$nowIsIssue) {
                    $opnameStatsByDate[$dt]['issues']--;
                }
            }

            $latestOpnameByDateProd[$key] = $diff;
        }

        $days = [];
        $cursor = $effectiveEnd->copy()->startOfDay();
        while ($cursor->gte($monthStart)) {
            $dt = $cursor->toDateString();
            $m = $movByDate[$dt] ?? [];
            $opStat = $opnameStatsByDate[$dt] ?? ['count' => 0, 'issues' => 0];

            $days[] = [
                'date' => $dt,
                'label' => $cursor->translatedFormat('d M Y (D)'),
                'restock' => (int) ($m['RESTOCK'] ?? 0),
                'return' => (int) ($m['RETURN'] ?? 0),
                'sale' => abs((int) ($m['SALE'] ?? 0)),
                'damage' => abs((int) ($m['DAMAGE'] ?? 0)),
                'adjustment' => (int) ($m['ADJUSTMENT'] ?? 0),
                'moved_products' => (int) ($distinctMoved[$dt] ?? 0),
                'opname_count' => $opStat['count'],
                'opname_issues' => $opStat['issues'],
                'has_issue' => $opStat['issues'] > 0,
            ];

            $cursor->subDay();
        }

        return $days;
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
