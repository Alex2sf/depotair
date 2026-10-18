<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CashBalance;
use App\Models\CashTransaction;
use App\Models\Order;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OwnerDashboardController extends Controller
{
    // 1. DASHBOARD UTAMA — RINGKASAN OMZET + KAS
    public function index(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = $request->user();
        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $start = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end   = $request->end_date   ? Carbon::parse($request->end_date)->endOfDay()     : Carbon::today()->endOfDay();

        $orders = Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'COMPLETE')
            ->get();

        $pendingOrders = Order::whereBetween('created_at', [$start, $end])
            ->whereIn('status', ['READY', 'ON_DELIVERY'])
            ->get();

        $pemasukan = $orders->groupBy(fn($o) => is_object($o->payment_type) ? $o->payment_type->value : $o->payment_type)
                           ->map(fn($g) => (int) $g->sum('total_amount'));

        $kasKasir = CashBalance::where('type', 'CASHIER')->first()->balance ?? 0;
        $kasBesar = CashBalance::where('type', 'MAIN')->first()->balance ?? 0;

        // Hitung pesanan menggantung yang lebih dari 3 hari lalu
        $cutoffOld = Carbon::now()->subDays(3)->startOfDay();
        $oldIncompleteOrders = Order::where('created_at', '<', $cutoffOld)
            ->whereNotIn('status', ['COMPLETE', 'CANCELLED'])
            ->get();

        return response()->json([
            'success' => true,
            'periode' => ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')],
            'omzet'         => (int) $orders->sum('total_amount'),
            'total_order'   => $orders->count(),
            'pending_omzet' => (int) $pendingOrders->sum('total_amount'),
            'pending_order' => $pendingOrders->count(),
            'old_incomplete_count'  => $oldIncompleteOrders->count(),
            'old_incomplete_amount' => (int) $oldIncompleteOrders->sum('total_amount'),
            'pemasukan' => [
                'tunai'    => (int) ($pemasukan['TUNAI'] ?? 0),
                'qris'     => (int) ($pemasukan['QRIS'] ?? 0),
                'transfer' => (int) ($pemasukan['TRANSFER'] ?? 0),
                'corporate'=> (int) ($pemasukan['CORPORATE'] ?? 0),
                'total'    => (int) $pemasukan->sum(),
            ],
            'kas_kasir'               => (int) $kasKasir,
            'kas_besar'               => (int) $kasBesar,
            'current_drawer_balance'  => (int) $kasKasir,
            'current_owner_balance'   => (int) $kasBesar,
            'total_kas'               => (int) ($kasKasir + $kasBesar),
        ]);
    }

    // 1.5 BULK COMPLETE PESANAN LAMA (> 3 HARI LALU)
    public function bulkCompleteOldOrders(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $days = (int) ($request->days ?? 3);
        $cutoff = Carbon::now()->subDays($days)->startOfDay();

        $orders = Order::where('created_at', '<', $cutoff)
            ->whereNotIn('status', ['COMPLETE', 'CANCELLED'])
            ->get();

        $count = $orders->count();
        if ($count === 0) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada pesanan menggantung yang lebih dari ' . $days . ' hari lalu.',
                'completed_count' => 0,
                'completed_amount' => 0,
            ]);
        }

        $totalAmount = (int) $orders->sum('total_amount');

        \Illuminate\Support\Facades\DB::transaction(function () use ($orders) {
            $now = Carbon::now();
            foreach ($orders as $order) {
                $order->update([
                    'status'         => 'COMPLETE',
                    'completed_time' => $now,
                ]);
            }
        });

        return response()->json([
            'success'          => true,
            'message'          => "Berhasil menyelesaikan {$count} pesanan lama (> {$days} hari lalu)!",
            'completed_count'  => $count,
            'completed_amount' => $totalAmount,
        ]);
    }

    // 2. RIWAYAT LENGKAP SEMUA TRANSAKSI
    public function transactions(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date'   => 'required|date_format:Y-m-d|after_or_equal:start_date',
            'page'       => 'nullable|integer|min:1',
        ]);

        $user = $request->user();
        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $start = Carbon::parse($request->start_date)->startOfDay();
        $end   = Carbon::parse($request->end_date)->endOfDay();

        $orderTrans = Order::whereBetween('created_at', [$start, $end])
            ->where('status', 'COMPLETE')
            ->select('created_at', 'total_amount', 'payment_type', 'order_number')
            ->get()
            ->map(fn($o) => [
                'waktu'           => $o->created_at,
                'tipe'            => 'Pemasukan',
                'jumlah'          => (int) $o->total_amount,
                'keterangan'      => "Order #{$o->order_number}",
                'kategori'        => is_object($o->payment_type) ? $o->payment_type->value : $o->payment_type,
                'oleh'            => '-',
                'atas_nama'       => '-',
                'proof_image_url' => null,
            ]);

        $cashTrans = CashTransaction::with(['recordedBy:id,name', 'onBehalfOf:id,name'])
            ->whereNull('order_id')
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->map(function ($t) {
                $isSetor = $t->type === 'DEPOSIT' && str_contains(strtolower((string) $t->description), 'setor');

                return [
                    'waktu'           => $t->created_at,
                    'tipe'            => $isSetor ? 'Setor Kas' : ($t->type === 'DEPOSIT' ? 'Pemasukan' : 'Pengeluaran'),
                    'jumlah'          => (int) $t->amount,
                    'keterangan'      => $t->description,
                    'kategori'        => $isSetor ? 'Setor Kas' : ($t->type === 'DEPOSIT' ? 'Deposit' : 'Expense'),
                    'oleh'            => $t->recordedBy?->name ?? 'Sistem',
                    'atas_nama'       => $t->onBehalfOf?->name ?? '-',
                    'proof_image_url' => $t->proof_image_url,
                ];
            });

        $semua = $orderTrans->merge($cashTrans)
            ->sortByDesc('waktu')
            ->values();

        $perPage = 50;
        $page = $request->page ?? 1;
        $paginated = $semua->forPage($page, $perPage);
        $total = $semua->count();

        return response()->json([
            'success' => true,
            'periode' => [
                'start' => $start->format('d M Y'),
                'end'   => $end->format('d M Y'),
            ],
            'total_transaksi' => $total,
            'data' => $paginated->map(fn($item) => [
                'waktu'           => $item['waktu']->format('d/m/Y H:i'),
                'tipe'            => $item['tipe'],
                'jumlah'          => $item['jumlah'],
                'keterangan'      => $item['keterangan'],
                'kategori'        => $item['kategori'],
                'oleh'            => $item['oleh'],
                'atas_nama'       => $item['atas_nama'] ?? '-',
                'proof_image_url' => $item['proof_image_url'] ?? null,
            ])->values()->all(),
            'pagination' => [
                'current_page' => (int) $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / $perPage),
            ]
        ]);
    }

    // 3. FITUR BARU: LIHAT INVENTORY — STOK SISTEM VS STOK RIIL + ESTIMASI KERUGIAN
    public function inventory(Request $request)
    {
        $user = $request->user();
        if (!in_array($user->role, ['owner', 'admin'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $search = $request->query('search');
        $start = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $end   = $request->end_date   ? Carbon::parse($request->end_date)->endOfDay()     : Carbon::today()->endOfDay();

        // 1. AMBIL ID PRODUK YANG TERJUAL HARI INI (ATAU RANGE TANGGAL)
        // Kita cari Order yang statusnya COMPLETE/ON_DELIVERY/READY/DRAFT (biasanya stock berkurang saat order dibuat/checkout)
        // Tapi "Sold Today" biasanya = Created Today. Adjust sesuai kebutuhan bisnis.
        $soldProductIds = \App\Models\OrderProduct::whereHas('order', function($q) use ($start, $end) {
                $q->whereBetween('created_at', [$start, $end])
                  ->whereIn('status', ['COMPLETE', 'ON_DELIVERY', 'READY']); // Asumsi saldo stok berkurang saat ini
            })
            ->pluck('product_id')
            ->unique();

        // 2. QUERY INVENTORY HANYA UNTUK PRODUK TERSEBUT
        $inventories = Inventory::with('product')
            ->whereIn('product_id', $soldProductIds) // FILTER UTAMA
            ->whereHas('product', fn($q) => $q->where('is_enabled', true))
            ->when($search, fn($q) => $q->whereHas('product', fn($p) => 
                $p->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
            ))
            ->get()
            ->map(function ($inv) {
                $selisih = $inv->quantity - $inv->real_quantity; // sistem - riil
                $hargaBeli = $inv->product->purchase_price ?? 0;
                $nilaiSelisih = abs($selisih) * $hargaBeli;

                return [
                    'product_id'     => $inv->product_id,
                    'nama'           => $inv->product->name,
                    'sku'            => $inv->product->sku,
                    'unit'           => $inv->product->unit,
                    'stok_sistem'    => (int) $inv->quantity,
                    'stok_riil'      => (int) $inv->real_quantity,
                    'selisih'        => (int) $selisih,
                    'status'         => $selisih == 0 ? 'normal' : ($selisih > 0 ? 'kurang' : 'lebih'),
                    'last_opname'    => $inv->last_opname_at?->format('d/m/Y H:i') ?? 'Belum opname',
                    'harga_beli'     => (int) $hargaBeli,
                    'nilai_selisih'  => (int) $nilaiSelisih,
                ];
            })
            ->sortByDesc('selisih')
            ->values();

        $totalKurang = $inventories->where('status', 'kurang')->sum('nilai_selisih');
        $totalLebih  = $inventories->where('status', 'lebih')->sum('nilai_selisih');

        return response()->json([
            'success' => true,
            'total_produk' => $inventories->count(),
            'ringkasan' => [
                'produk_kurang'       => $inventories->where('status', 'kurang')->count(),
                'produk_lebih'        => $inventories->where('status', 'lebih')->count(),
                'estimasi_rugi'       => (int) $totalKurang,
                'estimasi_untung'     => (int) $totalLebih,
            ],
            'data' => $inventories,
        ]);
    }


    
}