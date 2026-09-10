<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\CashBalance;
use App\Models\CashTransaction;
use App\Models\CashierPurchase;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Enums\MovementReason;
use Illuminate\Support\Facades\DB;

class CashierPurchaseController extends Controller
{
    /**
     * Catat Belanja / Pengeluaran Kasir dari Uang Laci
     */
    public function store(Request $request)
    {
        $request->validate([
            'category'    => 'required|in:STOCK,OPERATIONAL',
            'product_id'  => 'required_if:category,STOCK|nullable|exists:products,id',
            'quantity'    => 'required_if:category,STOCK|nullable|integer|min:1',
            'amount'      => 'required|integer|min:1',
            'description' => 'required|string|max:255',
            'proof_image' => 'nullable|image|max:10240', // Maks 10MB foto bukti/nota
        ]);

        $user = $request->user();
        $category = $request->category;
        $productId = $request->product_id;
        $quantity = (int) ($request->quantity ?? 0);
        $amount = (int) $request->amount;
        $description = $request->description;

        // Upload bukti foto nota jika ada
        $proofImagePath = null;
        if ($request->hasFile('proof_image')) {
            $proofImagePath = $request->file('proof_image')->store('purchases', 'public');
        }

        return DB::transaction(function () use ($user, $category, $productId, $quantity, $amount, $description, $proofImagePath) {
            // 1. Cek saldo laci kasir mencukupi
            $cashierBalance = CashBalance::where('type', CashBalance::CASHIER)->firstOrFail();

            if ($amount > $cashierBalance->balance) {
                return response()->json([
                    'success' => false,
                    'message' => "Saldo laci kasir tidak cukup!\nSaldo saat ini: Rp " . number_format($cashierBalance->balance, 0, ',', '.') .
                                 "\nBiaya belanja: Rp " . number_format($amount, 0, ',', '.'),
                ], 400);
            }

            // Potong saldo laci kasir
            $cashierBalance->decrement('balance', $amount);

            // 2. Jika belanja stok, tambah stok produk & catat InventoryMovement
            $productName = null;
            if ($category === 'STOCK' && $productId && $quantity > 0) {
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $productId],
                    ['quantity' => 0, 'low_stock_threshold' => 10]
                );

                $qtyBefore = $inventory->quantity;
                $qtyAfter = $qtyBefore + $quantity;

                // Update quantity di tabel inventories
                $inventory->update(['quantity' => $qtyAfter]);

                $product = Product::find($productId);
                $productName = $product?->name ?? 'Produk';

                // Catat mutasi stok
                InventoryMovement::create([
                    'inventory_id'    => $inventory->id,
                    'product_id'      => $productId,
                    'user_id'         => $user->id,
                    'type'            => 'in',
                    'quantity_before' => $qtyBefore,
                    'quantity_after'  => $qtyAfter,
                    'quantity_change' => $quantity,
                    'reason'          => MovementReason::RESTOCK->value,
                    'notes'           => "Belanja kasir: {$description} (+{$quantity} {$product?->unit})",
                    'description'     => "Belanja kasir: {$description}",
                ]);
            }

            // 3. Catat di cash_transactions sebagai EXPENSE agar terhitung di pembukuan dan saat tutup shift
            $descFull = ($category === 'STOCK' && $productName)
                ? "Belanja Stok: {$productName} x{$quantity} ({$description})"
                : "Operasional: {$description}";

            $cashTrans = CashTransaction::create([
                'type'        => CashTransaction::TYPE_EXPENSE,
                'amount'      => $amount,
                'description' => $descFull,
                'proof_image' => $proofImagePath,
                'recorded_by' => $user->id,
            ]);

            // 4. Catat ke tabel khusus cashier_purchases
            $purchase = CashierPurchase::create([
                'user_id'     => $user->id,
                'category'    => $category,
                'product_id'  => $category === 'STOCK' ? $productId : null,
                'quantity'    => $category === 'STOCK' ? $quantity : 0,
                'amount'      => $amount,
                'description' => $description,
                'proof_image' => $proofImagePath,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Belanja kasir berhasil dicatat. Saldo kasir terpotong' . ($category === 'STOCK' ? ' & stok berhasil ditambah!' : '!'),
                'data'    => [
                    'purchase_id'     => $purchase->id,
                    'category'        => $purchase->category,
                    'amount'          => $purchase->amount,
                    'product_name'    => $productName,
                    'quantity_added'  => $quantity,
                    'drawer_balance'  => (int) $cashierBalance->fresh()->balance,
                    'proof_image_url' => $purchase->proof_image_url,
                ]
            ]);
        });
    }

    /**
     * Riwayat Pembelian / Belanja Kasir
     */
    public function history(Request $request)
    {
        $query = CashierPurchase::with(['user:id,name', 'product:id,name,unit,sku'])
            ->orderByDesc('created_at');

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->category) {
            $query->where('category', $request->category);
        }

        $purchases = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $purchases->map(fn($p) => [
                'id'              => $p->id,
                'category'        => $p->category,
                'product_id'      => $p->product_id,
                'product_name'    => $p->product?->name,
                'product_sku'     => $p->product?->sku,
                'unit'            => $p->product?->unit ?? 'pcs',
                'quantity'        => (int) $p->quantity,
                'amount'          => (int) $p->amount,
                'description'     => $p->description,
                'proof_image_url' => $p->proof_image_url,
                'cashier_name'    => $p->user?->name ?? 'Kasir',
                'created_at'      => $p->created_at->format('d/m/Y H:i'),
            ]),
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page'    => $purchases->lastPage(),
                'total'        => $purchases->total(),
            ]
        ]);
    }
}
