<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockProcurement;
use App\Models\StockProcurementItem;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\CashBalance;
use App\Models\CashTransaction;
use App\Enums\MovementReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockProcurementController extends Controller
{
    /**
     * 1. Daftar Riwayat / List Pengajuan Pengadaan Stok
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = StockProcurement::with([
            'requester:id,name',
            'approver:id,name',
            'receiver:id,name',
            'items.product.inventory',
        ])->latest('id');

        if ($request->filled('status')) {
            $query->where('status', strtoupper($request->status));
        }

        $procurements = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $procurements,
        ]);
    }

    /**
     * 1b. Katalog Produk & Rekomendasi Restock Cerdas untuk Keranjang Pengadaan
     */
    public function catalog(Request $request)
    {
        $today = Carbon::today();

        // 1. Ambil status pengajuan PENDING
        $pendingItems = DB::table('stock_procurement_items')
            ->join('stock_procurements', 'stock_procurement_items.procurement_id', '=', 'stock_procurements.id')
            ->where('stock_procurements.status', 'PENDING')
            ->pluck('stock_procurement_items.quantity_requested', 'stock_procurement_items.product_id')
            ->toArray();

        // 2. Hitung penjualan hari ini per produk
        $salesToday = DB::table('order_product')
            ->join('orders', 'order_product.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['COMPLETE', 'ON_DELIVERY'])
            ->where('orders.created_at', '>=', $today->copy()->startOfDay())
            ->groupBy('order_product.product_id')
            ->select('order_product.product_id', DB::raw('SUM(order_product.quantity) as total_sold'))
            ->pluck('total_sold', 'product_id')
            ->toArray();

        // 3. Ambil seluruh produk aktif dengan relasi inventory
        $products = Product::with('inventory')
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get()
            ->map(function ($product) use ($salesToday, $pendingItems) {
                $inventory = $product->inventory;
                $currentStock = (int) ($inventory?->quantity ?? 0);
                $threshold = (int) ($inventory?->low_stock_threshold ?? 10);
                $soldToday = (int) ($salesToday[$product->id] ?? 0);
                $isLowStock = $currentStock <= $threshold;
                $isRecommended = $isLowStock || $soldToday > 0;

                // Saran jumlah restock cerdas
                $suggestedQty = 10;
                if ($isLowStock) {
                    $deficit = max(0, ($threshold * 2) - $currentStock);
                    $suggestedQty = max($deficit, $soldToday, 10);
                } else if ($soldToday > 0) {
                    $suggestedQty = max($soldToday, 10);
                }

                return [
                    'id'                      => $product->id,
                    'name'                    => $product->name,
                    'sku'                     => $product->sku,
                    'product_type'            => $product->product_type ?? 'REFILL',
                    'unit'                    => $product->unit,
                    'price'                   => (int) ($product->price ?? 0),
                    'cogs'                    => (int) ($product->cogs ?? 0),
                    'image_url'               => $product->image_url ?? null,
                    'current_stock'           => $currentStock,
                    'low_stock_threshold'     => $threshold,
                    'sold_today'              => $soldToday,
                    'is_low_stock'            => $isLowStock,
                    'is_recommended'          => $isRecommended,
                    'suggested_quantity'      => $suggestedQty,
                    'pending_procurement_qty' => (int) ($pendingItems[$product->id] ?? 0),
                ];
            });

        $recommendations = $products->filter(fn($p) => $p['is_recommended'])->values();

        $productTypes = [
            ['value' => 'SEMUA', 'label' => 'Semua'],
        ];
        if (\Illuminate\Support\Facades\Schema::hasTable('product_types')) {
            $types = DB::table('product_types')->where('is_active', 1)->get();
            foreach ($types as $t) {
                $productTypes[] = [
                    'value' => $t->code,
                    'label' => $t->name,
                ];
            }
        }

        return response()->json([
            'success'         => true,
            'recommendations' => $recommendations,
            'all_products'    => $products->values(),
            'total_products'  => $products->count(),
            'low_stock_count' => $products->where('is_low_stock', true)->count(),
            'product_types'   => $productTypes,
        ]);
    }

    /**
     * 2. Detail Pengajuan Pengadaan Stok
     */
    public function show($id)
    {
        $procurement = StockProcurement::with([
            'requester:id,name',
            'approver:id,name',
            'receiver:id,name',
            'items.product.inventory',
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $procurement,
        ]);
    }

    /**
     * 3. Kasir / Admin membuat Pengajuan Pengadaan Stok baru
     */
    public function store(Request $request)
    {
        $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
            'notes'                 => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        return DB::transaction(function () use ($request, $user) {
            $datePrefix = Carbon::now()->format('Ymd');

            // Cari pengajuan PENDING milik user hari ini yang belum diproses Owner
            $procurement = StockProcurement::where('requested_by', $user->id)
                ->where('status', 'PENDING')
                ->whereDate('created_at', Carbon::today())
                ->latest()
                ->first();

            if (!$procurement) {
                $todayCount = StockProcurement::whereDate('created_at', Carbon::today())->count() + 1;
                $procurementNumber = 'PR-' . $datePrefix . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

                $procurement = StockProcurement::create([
                    'procurement_number'     => $procurementNumber,
                    'requested_by'           => $user->id,
                    'total_estimated_amount' => 0,
                    'total_approved_amount'  => 0,
                    'status'                 => 'PENDING',
                    'notes'                  => $request->notes,
                ]);
            } else if ($request->filled('notes')) {
                $procurement->update(['notes' => $request->notes]);
            }

            // Kumpulkan ID produk yang diajukan kali ini
            $submittedProductIds = collect($request->items)->pluck('product_id')->toArray();

            // Bersihkan barang-barang lama di nota pending ini yang tidak diajukan lagi (overwrite / sync)
            StockProcurementItem::where('procurement_id', $procurement->id)
                ->whereNotIn('product_id', $submittedProductIds)
                ->delete();

            foreach ($request->items as $itemData) {
                $product = Product::with('inventory')->findOrFail($itemData['product_id']);
                $currentStock = $product->inventory?->quantity ?? 0;
                $quantity = (int) $itemData['quantity'];
                $unitCogs = (int) ($product->cogs ?? 0);
                $subtotal = $quantity * $unitCogs;

                // Cek apakah produk ini sudah pernah diajukan sebelumnya di tiket pending ini
                $existingItem = StockProcurementItem::where('procurement_id', $procurement->id)
                    ->where('product_id', $product->id)
                    ->first();

                if ($existingItem) {
                    // Otomatis diperbarui ke kuantitas TERAKHIR yang diajukan kasir
                    $existingItem->update([
                        'current_stock'       => $currentStock,
                        'quantity_requested'  => $quantity,
                        'unit_cogs_requested' => $unitCogs,
                        'subtotal_requested'  => $subtotal,
                        'quantity_approved'   => $quantity,
                        'unit_cogs_approved'  => $unitCogs,
                        'subtotal_approved'   => $subtotal,
                        'is_approved'         => true,
                    ]);
                } else {
                    StockProcurementItem::create([
                        'procurement_id'      => $procurement->id,
                        'product_id'          => $product->id,
                        'current_stock'       => $currentStock,
                        'quantity_requested'  => $quantity,
                        'unit_cogs_requested' => $unitCogs,
                        'subtotal_requested'  => $subtotal,
                        'quantity_approved'   => $quantity,
                        'unit_cogs_approved'  => $unitCogs,
                        'subtotal_approved'   => $subtotal,
                        'is_approved'         => true,
                    ]);
                }
            }

            // Hitung ulang total pengajuan
            $totalEstimated = $procurement->items()->sum('subtotal_requested');
            $procurement->update([
                'total_estimated_amount' => $totalEstimated,
                'total_approved_amount'  => $totalEstimated,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan belanja stok berhasil dikirim ke Owner!',
                'data'    => $procurement->load('items.product'),
            ], 200);
        });
    }

    /**
     * 4. Owner / Admin me-review Pengajuan (Approve / Reject)
     * Bisa edit quantity, edit harga modal cogs, atau hapus item.
     * Jika di-approve, saldo KAS BESAR (cash_balances.MAIN) otomatis berkurang!
     */
    public function review(Request $request, $id)
    {
        $request->validate([
            'action'                 => 'required|in:APPROVE,REJECT',
            'owner_notes'            => 'nullable|string|max:500',
            'items'                  => 'nullable|array',
            'items.*.item_id'        => 'required_with:items|exists:stock_procurement_items,id',
            'items.*.quantity'       => 'nullable|integer|min:0',
            'items.*.unit_cogs'      => 'nullable|integer|min:0',
            'items.*.is_approved'    => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak. Hanya Owner / Admin yang dapat mereview.'], 403);
        }

        $procurement = StockProcurement::with('items.product')->findOrFail($id);

        if ($procurement->status !== 'PENDING') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini sudah pernah diproses sebelumnya (Status: ' . $procurement->status . ').',
            ], 422);
        }

        if ($request->action === 'REJECT') {
            $procurement->update([
                'status'      => 'REJECTED',
                'approved_by' => $user->id,
                'owner_notes' => $request->owner_notes ?? 'Ditolak oleh Owner',
                'approved_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan belanja stok telah ditolak.',
                'data'    => $procurement,
            ]);
        }

        // AKSI: APPROVE
        return DB::transaction(function () use ($request, $user, $procurement) {
            $totalApproved = 0;
            $itemsMap = collect($request->items ?? [])->keyBy('item_id');

            foreach ($procurement->items as $item) {
                $override = $itemsMap->get($item->id);

                $isApproved = true;
                $approvedQty = $item->quantity_requested;
                $approvedUnitCogs = $item->unit_cogs_requested;

                if ($override) {
                    if (isset($override['is_approved'])) {
                        $isApproved = (bool) $override['is_approved'];
                    }
                    if (isset($override['quantity'])) {
                        $approvedQty = (int) $override['quantity'];
                    }
                    if (isset($override['unit_cogs'])) {
                        $approvedUnitCogs = (int) $override['unit_cogs'];
                    }
                }

                // Jika qty 0, otomatis tandai tidak disetujui (dihapus owner)
                if ($approvedQty <= 0) {
                    $isApproved = false;
                }

                $subtotalApproved = $isApproved ? ($approvedQty * $approvedUnitCogs) : 0;

                $item->update([
                    'is_approved'        => $isApproved,
                    'quantity_approved'  => $isApproved ? $approvedQty : 0,
                    'unit_cogs_approved' => $approvedUnitCogs,
                    'subtotal_approved'  => $subtotalApproved,
                ]);

                if ($isApproved) {
                    $totalApproved += $subtotalApproved;

                    // Update harga modal cogs di master produk jika owner mengubah harga
                    if ($approvedUnitCogs > 0 && $approvedUnitCogs !== (int)$item->product->cogs) {
                        $item->product->update(['cogs' => $approvedUnitCogs]);
                    }
                }
            }

            if ($totalApproved <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada barang yang disetujui untuk dibeli. Silakan pilih tolak jika tidak ada pengadaan.',
                ], 422);
            }

            // POTONG SALDO KAS BESAR (cash_balances.MAIN)
            $mainBalance = CashBalance::where('type', 'MAIN')->firstOrFail();

            if ($totalApproved > $mainBalance->balance) {
                return response()->json([
                    'success' => false,
                    'message' => "Saldo Kas Besar tidak mencukupi! (Saldo: Rp " . number_format($mainBalance->balance, 0, ',', '.') . ", Dibutuhkan: Rp " . number_format($totalApproved, 0, ',', '.') . ")",
                ], 422);
            }

            // 1. Kurangi Kas Besar
            $mainBalance->decrement('balance', $totalApproved);

            // 2. Catat Mutasi Pengeluaran Kas Besar
            CashTransaction::create([
                'type'        => CashTransaction::TYPE_EXPENSE,
                'amount'      => $totalApproved,
                'description' => "Pengadaan Stok #" . $procurement->procurement_number . " (Disetujui Owner)",
                'recorded_by' => $user->id,
            ]);

            // 3. Update Procurement
            $procurement->update([
                'status'                => 'APPROVED',
                'approved_by'           => $user->id,
                'approved_at'           => now(),
                'total_approved_amount' => $totalApproved,
                'owner_notes'           => $request->owner_notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengajuan berhasil disetujui! Kas Besar telah dipotong sebesar Rp ' . number_format($totalApproved, 0, ',', '.'),
                'data'    => $procurement->fresh(['items.product', 'approver']),
            ]);
        });
    }

    /**
     * 5. Kasir / Admin mengonfirmasi Barang Tiba di Toko (Goods Received)
     * Mengupload nota / struk dan menambah stok produk secara otomatis!
     */
    public function receive(Request $request, $id)
    {
        $request->validate([
            'proof_image' => 'nullable|image|max:10240', // Maks 10MB
        ]);

        $user = $request->user();
        $procurement = StockProcurement::with('items.product.inventory')->findOrFail($id);

        if ($procurement->status !== 'APPROVED') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status APPROVED yang dapat diterima barangnya.',
            ], 422);
        }

        // Upload bukti foto nota supplier jika ada
        $proofImagePath = null;
        if ($request->hasFile('proof_image')) {
            try {
                $proofImagePath = $request->file('proof_image')->store('procurements', 'public');
            } catch (\Exception $e) {
                $targetFolder = public_path('procurements');
                if (!file_exists($targetFolder)) {
                    @mkdir($targetFolder, 0755, true);
                }
                $filename = 'procurement_' . time() . '_' . uniqid() . '.' . $request->file('proof_image')->getClientOriginalExtension();
                $request->file('proof_image')->move($targetFolder, $filename);
                $proofImagePath = 'procurements/' . $filename;
            }
        }

        return DB::transaction(function () use ($procurement, $user, $proofImagePath) {
            foreach ($procurement->items as $item) {
                if (!$item->is_approved || $item->quantity_approved <= 0) {
                    continue;
                }

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $item->product_id],
                    [
                        'quantity'            => 0,
                        'real_quantity'       => 0,
                        'low_stock_threshold' => 5,
                    ]
                );

                $before = $inventory->quantity;
                $qtyAdd = $item->quantity_approved;
                $after  = $before + $qtyAdd;

                $inventory->quantity = $after;
                $inventory->save();

                // Catat di Inventory Movements
                InventoryMovement::create([
                    'product_id'      => $item->product_id,
                    'order_id'        => null,
                    'user_id'         => $user->id,
                    'quantity_before' => $before,
                    'quantity_change' => $qtyAdd,
                    'quantity_after'  => $after,
                    'reason'          => MovementReason::RESTOCK,
                    'notes'           => "Penerimaan Stok #" . $procurement->procurement_number . " (" . ($item->product->name ?? 'Produk') . ")",
                ]);
            }

            $procurement->update([
                'status'       => 'RECEIVED',
                'received_by'  => $user->id,
                'received_at'  => now(),
                'proof_image'  => $proofImagePath ?? $procurement->proof_image,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Barang berhasil diterima! Stok sistem telah otomatis bertambah.',
                'data'    => $procurement->fresh(['items.product', 'receiver']),
            ]);
        });
    }
}
