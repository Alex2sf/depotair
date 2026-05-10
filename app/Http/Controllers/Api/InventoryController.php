<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OpnameRequest;
use App\Models\Inventory;
use App\Models\InventoryMovement; // Tetap di-import, tapi tidak digunakan di fungsi opname()
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // INI YANG KURANG BRO!!!

class InventoryController extends Controller
{
    // Endpoint: POST /api/inventory/opname
    public function opname(OpnameRequest $request)
    {
        $user = $request->user();
        $notes = $request->notes ?? 'Opname rutin malam';

        DB::transaction(function () use ($request) {
            foreach ($request->opname as $item) {
                $inventory = Inventory::where('product_id', $item['product_id'])->firstOrFail();
                
                $realQuantityBaru = (int) $item['real_quantity'];
                
                // HANYA UPDATE real_quantity dan last_opname_at di tabel Inventory
                $inventory->update([
                    'real_quantity'   => $realQuantityBaru,
                    'last_opname_at'  => now(),
                ]);
                
                // !!! BAGIAN PENCATATAN KE InventoryMovement DIHAPUS !!!
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Opname stok berhasil! Real Quantity sudah diperbarui.',
            'opname_at' => now()->format('d M Y H:i')
        ]);
    }

    // Bonus: List stok + real_quantity buat form opname malam
    public function listForOpname()
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
    
        // 1. DAFTAR PRODUK YANG TERJUAL HARI INI (NAMA AJA)
        // 1. DAFTAR PRODUK YANG TERJUAL (Sejak Opname Terakhir / Hari Ini)
        // Kita join logikanya dengan tabel inventories untuk cek last_opname_at masing-masing produk
        $soldProductsToday = DB::table('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->join('products', 'order_product.product_id', '=', 'products.id')
            ->join('inventories', 'products.id', '=', 'inventories.product_id')
            ->whereIn('orders.status', ['COMPLETE', 'ON_DELIVERY'])
            ->where('products.is_enabled', true)
            // Cek created_at > last_opname_at (atau hari ini jika null)
            ->whereRaw('orders.created_at > COALESCE(inventories.last_opname_at, ?)', [$today->format('Y-m-d 00:00:00')])
            ->select('products.id as product_id', 'products.name')
            ->distinct()
            ->orderBy('products.name')
            ->get()
            ->pluck('name')
            ->toArray();
    
        // 2. STOK OPNAME BIASA (sama kayak sebelumnya)
        $inventories = Inventory::with('product')
            ->whereHas('product', fn($q) => $q->where('is_enabled', true))
            ->get()
            ->map(function ($inv) use ($today) {
                // Perhitungan terjual HARI INI dimodifikasi sesuai request:
                // "Reset setelah simpan". Jadi hitung penjualan sejak terakhir opname.
                // Jika belum pernah opname, fallback ke hari ini jam 00:00
                $lastOpnameTime = $inv->last_opname_at ?? $today->copy()->startOfDay();

                $soldToday = DB::table('order_product')
                    ->join('orders', 'order_product.order_id', '=', 'orders.id')
                    ->where('order_product.product_id', $inv->product_id)
                    ->whereIn('orders.status', ['COMPLETE', 'ON_DELIVERY'])
                    // LOGIC BARU: Hitung yang terjual SETELAH waktu opname terakhir
                    ->where('orders.created_at', '>', $lastOpnameTime)
                    ->sum('order_product.quantity');
    
                $realQty = $inv->real_quantity ?? $inv->quantity;
                $lastOpname = $inv->last_opname_at?->format('d/m/Y H:i') ?? '-';
                $selisih = $inv->quantity - $realQty;
    
                return [
                    'product_id'       => $inv->product_id,
                    'nama'             => $inv->product->name,
                    'sku'              => $inv->product->sku,
                    'unit'             => $inv->product->unit,
                    'stok_awal_hari'   => (int) ($inv->quantity + $soldToday), 
                    'terjual_hari_ini' => (int) $soldToday,
                    'stok_sistem'      => (int) $inv->quantity,
                    'stok_riil'        => (int) $realQty,
                    'selisih'          => (int) $selisih,
                    'status'           => $selisih == 0 ? 'normal' : ($selisih > 0 ? 'kurang' : 'lebih'),
                    'last_opname'      => $lastOpname,
                ];
            })
            ->sortByDesc('selisih')
            ->values();
    
        // 3. RETURN SEMUA: STOK + DAFTAR PRODUK TERJUAL HARI INI
        return response()->json([
            'success' => true,
            'opname_info' => 'Opname per ' . now()->format('d M Y H:i'),
            'total_terjual_hari_ini' => $inventories->sum('terjual_hari_ini'),
            'total_jenis_terjual'    => count($soldProductsToday),
            'produk_terjual_hari_ini' => $soldProductsToday, // INI YANG KAMU MAU BRO!
            'data' => $inventories,
            'ringkasan' => [
                'produk_kurang' => $inventories->where('status', 'kurang')->count(),
                'produk_lebih'  => $inventories->where('status', 'lebih')->count(),
                'total_selisih' => $inventories->sum('selisih'),
            ]
        ]);
    }
    // Endpoint: POST /api/inventory/adjust
    public function adjust(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
            'reason'     => 'required|string', // RESTOCK, DAMAGE, RETURN, ADJUSTMENT
            'notes'      => 'nullable|string',
            'direction'  => 'nullable|in:in,out', // Opsional, wajib jika reason = ADJUSTMENT
        ]);

        $user = $request->user();
        $reason = \App\Enums\MovementReason::tryFrom($request->reason);

        if (!$reason) {
            return response()->json(['success' => false, 'message' => 'Alasan tidak valid.'], 400);
        }

        // Tentukan arah (masuk/keluar)
        $direction = $request->direction;

        // Auto-detect direction based on reason if not provided or to enforce logic
        if ($reason == \App\Enums\MovementReason::RESTOCK || $reason == \App\Enums\MovementReason::RETURN) {
            $direction = 'in';
        } elseif ($reason == \App\Enums\MovementReason::DAMAGE || $reason == \App\Enums\MovementReason::SALE) {
            $direction = 'out';
        }

        // Untuk ADJUSTMENT, direction wajib ada
        if ($reason == \App\Enums\MovementReason::ADJUSTMENT && !$direction) {
            return response()->json(['success' => false, 'message' => 'Untuk Penyesuaian, arah (masuk/keluar) wajib dipilih.'], 422);
        }

        $change = ($direction === 'in') ? $request->quantity : -($request->quantity);

        DB::transaction(function () use ($request, $user, $change, $reason) {
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $request->product_id],
                ['quantity' => 0, 'low_stock_threshold' => 10]
            );

            $qtyBefore = $inventory->quantity;
            $qtyAfter  = $qtyBefore + $change;

            // Update Inventory
            $inventory->update(['quantity' => $qtyAfter]);

            // Catat Movement
            InventoryMovement::create([
                'inventory_id'    => $inventory->id,
                'product_id'      => $request->product_id,
                'user_id'         => $user->id,
                'type'            => ($change > 0) ? 'in' : 'out',
                'quantity_before' => $qtyBefore,
                'quantity_after'  => $qtyAfter,
                'quantity_change' => $change,
                'reason'          => $reason->value,
                'description'     => $request->notes ?? $reason->getLabel(),
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Stok berhasil diperbarui (' . $reason->getLabel() . ')',
        ]);
    }
}