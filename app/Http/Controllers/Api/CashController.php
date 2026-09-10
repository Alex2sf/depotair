<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CashTransactionRequest;
use App\Http\Requests\Api\CloseShiftRequest;
use App\Models\CashBalance;
use App\Models\CashTransaction;
use App\Models\Order;
use Illuminate\Http\Request;           // INI YANG KAMU LUPA!!!
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Requests\Api\DepositToMainRequest; 

class CashController extends Controller
{
    // 1. Catat transaksi manual (modal awal, beli plastik, dll)
    public function store(CashTransactionRequest $request)
    {
        $user   = $request->user();
        $type   = $request->type;
        $amount = $request->amount;

        return DB::transaction(function () use ($request, $user, $type, $amount) {

            // CARI SALDO KASIR SEKARANG
            $saldoKasirSekarang = CashBalance::where('type', CashBalance::CASHIER)->firstOrFail()->balance;

            // VALIDASI KHUSUS EXPENSE — INI YANG KAMU TUNGGU-TUNGGU!
            if ($type === CashTransaction::TYPE_EXPENSE) {
                if ($amount > $saldoKasirSekarang) {
                    return response()->json([
                        'success' => false,
                        'message' => "Gagal! Saldo kasir tidak cukup.\nSaldo saat ini: Rp " . number_format($saldoKasirSekarang, 0, ',', '.') . 
                                    "\nDiminta keluar: Rp " . number_format($amount, 0, ',', '.'),
                    ], 400);
                }
            }

            // LANJUT PROSES SEPERTI BIASA
            if ($type === CashTransaction::TYPE_DEPOSIT) {
                CashBalance::where('type', CashBalance::CASHIER)->increment('balance', $amount);
                if (str_contains(strtolower($request->description), 'modal')) {
                    CashBalance::where('type', CashBalance::MAIN)->decrement('balance', $amount);
                }
            } else {
                // EXPENSE — PASTI AMAN KARENA SUDAH DICEK DI ATAS
                CashBalance::where('type', CashBalance::CASHIER)->decrement('balance', $amount);
            }

            CashTransaction::create([
                'type'        => $type,
                'amount'      => $amount,
                'description' => $request->description,
                'recorded_by' => $user->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi kas berhasil dicatat',
                'saldo_sekarang' => (int) CashBalance::where('type', CashBalance::CASHIER)->first()->balance, // bonus info
            ]);
        });
    }

    public function depositToMain(Request $request)
    {
        $request->validate([
            'amount'        => 'required|integer|min:0',
            'on_behalf_of'  => 'nullable|exists:users,id',
            'notes'         => 'nullable|string|max:200',
            'proof_image'   => 'nullable|image|mimes:jpeg,jpg,png,webp|max:10240',
        ]);

        $amount       = $request->amount;
        $recordedBy   = $request->user()->id;
        $onBehalfOf   = $request->on_behalf_of ?? $recordedBy;

        // Simpan foto bukti jika ada
        $proofImagePath = null;
        if ($request->hasFile('proof_image')) {
            try {
                $proofImagePath = $request->file('proof_image')->store('deposits', 'public');
            } catch (\Exception $e) {
                // Fallback jika storage link atau permission error
                $targetFolder = public_path('deposits');
                if (!file_exists($targetFolder)) {
                    @mkdir($targetFolder, 0755, true);
                }
                $filename = 'deposit_' . time() . '_' . uniqid() . '.' . $request->file('proof_image')->getClientOriginalExtension();
                $request->file('proof_image')->move($targetFolder, $filename);
                $proofImagePath = 'deposits/' . $filename;
            }
        }

        return DB::transaction(function () use ($amount, $recordedBy, $onBehalfOf, $proofImagePath, $request) {
            $cashier = CashBalance::firstOrCreate(
                ['type' => CashBalance::CASHIER],
                ['balance' => 0]
            );

            if ($amount > $cashier->balance) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Saldo kasir tidak cukup! Saldo saat ini: Rp ' . number_format($cashier->balance, 0, ',', '.')
                ], 400);
            }

            $main = CashBalance::firstOrCreate(
                ['type' => CashBalance::MAIN],
                ['balance' => 0]
            );
            $cashier->decrement('balance', $amount);
            $main->increment('balance', $amount);

            $userName = \App\Models\User::find($onBehalfOf)?->name ?? 'Unknown';
            $noteDesc = $request->notes ? " ({$request->notes})" : "";

            $transaction = CashTransaction::create([
                'type'         => CashTransaction::TYPE_DEPOSIT,
                'amount'       => $amount,
                'description'  => "Setor ke kas besar atas nama {$userName}{$noteDesc}",
                'proof_image'  => $proofImagePath,
                'recorded_by'  => $recordedBy,
                'on_behalf_of' => $onBehalfOf,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Berhasil setor Rp " . number_format($amount, 0, ',', '.') . " atas nama {$userName}!",
                'data'    => [
                    'saldo_kasir'     => (int) $cashier->fresh()->balance,
                    'saldo_kas_besar' => (int) $main->fresh()->balance,
                    'proof_image_url' => $transaction->proof_image_url,
                ],
            ]);
        });
    }
    // 3. Dashboard kas (khusus owner & admin) - DENGAN PEMASUKAN PER PAYMENT TYPE + DATE RANGE
    public function dashboard(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date_format:Y-m-d',
            'end_date'   => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $user = request()->user();
        if (!in_array($user->role, ['owner', 'admin','kasir'])) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak'], 403);
        }

        $startDate = $request->start_date ? Carbon::parse($request->start_date)->startOfDay() : Carbon::today()->startOfDay();
        $endDate = $request->end_date ? Carbon::parse($request->end_date)->endOfDay() : Carbon::today()->endOfDay();

        $cashier = CashBalance::where('type', 'CASHIER')->first()->balance;
        $main    = CashBalance::where('type', 'MAIN')->first()->balance;

        $todayTransactions = CashTransaction::whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        // PEMASUKAN PER PAYMENT TYPE DARI ORDER (INI YANG KAMU MAU BRO!)
        $pemasukanOrders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['COMPLETE', 'ON_DELIVERY'])
            ->select([
                'payment_type',
                DB::raw('SUM(total_amount) as total_pemasukan')
            ])
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type')
            ->map(fn($item) => (int) $item->total_pemasukan);

        $pemasukan = [
            'TUNAI'    => $pemasukanOrders['TUNAI'] ?? 0,
            'QRIS'     => $pemasukanOrders['QRIS'] ?? 0,
            'TRANSFER' => $pemasukanOrders['TRANSFER'] ?? 0,
            'CORPORATE'=> $pemasukanOrders['CORPORATE'] ?? 0,
        ];

        return response()->json([
            'success' => true,
            'kas_kasir' => (int) $cashier,
            'kas_besar' => (int) $main,
            'total_kas' => $cashier + $main,
            'riwayat'   => $todayTransactions->map(fn($t) => [
                'waktu'       => $t->created_at->format('H:i'),
                'tipe'        => $t->type === 'DEPOSIT' ? 'Masuk' : 'Keluar',
                'jumlah'      => (int) $t->amount,
                'keterangan'  => $t->description,
                'oleh'        => $t->recordedBy?->name ?? 'Sistem',
            ]),
            'pemasukan' => $pemasukan,  // INI YANG BARU!!! PEMASUKAN PER TYPE
            'range' => [
                'start' => $startDate->format('Y-m-d'),
                'end'   => $endDate->format('Y-m-d'),
            ],
        ]);
    }
    // LIST USERS UNTUK DROPDOWN "SETOR ATAS NAMA"
    public function listCashiers()
    {
        // Return semua user untuk dipilih
        $users = \App\Models\User::select('id', 'name', 'role')->get();

        return response()->json([
            'success' => true,
            'data'    => $users
        ]);
    }
}