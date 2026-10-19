<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * 1. Cek atau buka shift aktif kasir yang login
     */
    public function current(Request $request)
    {
        $user = $request->user();

        // 1. Ambil SATU-SATUNYA shift OPEN yang ada di seluruh toko (1 laci fisik)
        $activeShift = \App\Models\CashierShift::where('status', 'OPEN')
            ->with('user:id,name')
            ->latest('id')
            ->first();

        // 2. Ambil saldo laci kasir saat ini
        $cashierBalance = (int) (\App\Models\CashBalance::where('type', 'CASHIER')->value('balance') ?? 0);

        // Jika sama sekali belum ada shift OPEN di toko:
        if (!$activeShift) {
            // Buat shift baru untuk user yang sedang aktif
            $activeShift = \App\Models\CashierShift::create([
                'user_id'        => $user->id,
                'start_time'     => now(),
                'starting_cash'  => $cashierBalance,
                'cash_sales'     => 0,
                'cash_expenses'  => 0,
                'cash_deposited' => 0,
                'expected_cash'  => $cashierBalance,
                'status'         => 'OPEN',
            ]);
        } else {
            // JIKA ADA SHIFT OPEN LEBIH DARI 1 (Legacy bug / duplikat), auto-close yang lama
            \App\Models\CashierShift::where('status', 'OPEN')
                ->where('id', '!=', $activeShift->id)
                ->update([
                    'status'   => 'CLOSED',
                    'end_time' => now(),
                    'notes'    => 'Auto-closed by system: single active shift enforcement',
                ]);
        }

        // Cek apakah shift OPEN ini milik user yang sedang login atau kasir lain
        $isCurrentUser = ($activeShift->user_id === $user->id);

        // Hitung akumulasi real-time selama shift ini berjalan
        $shiftStart = $activeShift->start_time;

        // Total penjualan TUNAI selama shift
        $cashSales = (int) \App\Models\Order::where('created_at', '>=', $shiftStart)
            ->where('status', '!=', 'CANCELLED')
            ->where('payment_type', 'TUNAI')
            ->sum('total_amount');

        // Total pengeluaran kas laci selama shift (kecualikan void order dari shift yang sama agar tidak terpotong 2x)
        $cashExpenses = (int) \App\Models\CashTransaction::where('created_at', '>=', $shiftStart)
            ->where('type', 'EXPENSE')
            ->where(function ($q) use ($shiftStart) {
                $q->whereNull('order_id')
                  ->orWhereHas('order', fn ($oq) => $oq->where('created_at', '<', $shiftStart));
            })
            ->sum('amount');

        // Total setoran ke kas besar selama shift
        $cashDeposited = (int) \App\Models\CashTransaction::where('created_at', '>=', $shiftStart)
            ->where('type', 'DEPOSIT')
            ->where('description', 'like', '%Setor ke kas besar%')
            ->sum('amount');

        $expectedCash = $activeShift->starting_cash + $cashSales - $cashExpenses - $cashDeposited;

        // Update kalkulasi sementara
        $activeShift->update([
            'cash_sales'     => $cashSales,
            'cash_expenses'  => $cashExpenses,
            'cash_deposited' => $cashDeposited,
            'expected_cash'  => $expectedCash,
        ]);

        // Cari shift terakhir yang sudah DITUTUP sebelum shift aktif ini
        $lastClosedShift = \App\Models\CashierShift::where('status', 'CLOSED')
            ->where('id', '!=', $activeShift->id)
            ->with('user:id,name')
            ->latest('end_time')
            ->first();

        // Info jika shift ini milik kasir sebelumnya yang belum tutup shift
        $unclosedShiftWarning = !$isCurrentUser ? [
            'other_user_name' => $activeShift->user?->name ?? 'Kasir Lain',
            'opened_at'       => $activeShift->start_time->format('d/m/Y H:i'),
        ] : null;

        return response()->json([
            'success' => true,
            'is_current_user' => $isCurrentUser,
            'shift'   => [
                'id'             => $activeShift->id,
                'cashier_name'   => $activeShift->user?->name ?? $user->name,
                'user_id'        => $activeShift->user_id,
                'start_time'     => $activeShift->start_time->format('d/m/Y H:i'),
                'starting_cash'  => (int) $activeShift->starting_cash,
                'cash_sales'     => (int) $cashSales,
                'cash_expenses'  => (int) $cashExpenses,
                'cash_deposited' => (int) $cashDeposited,
                'expected_cash'  => (int) $expectedCash,
                'current_drawer_balance' => (int) $cashierBalance,
                'status'         => $activeShift->status,
            ],
            'last_closed_shift' => $lastClosedShift ? [
                'id'             => $lastClosedShift->id,
                'cashier_name'   => $lastClosedShift->user?->name ?? 'Kasir Sebelumnya',
                'start_time'     => $lastClosedShift->start_time->format('d/m/Y H:i'),
                'end_time'       => $lastClosedShift->end_time?->format('d/m/Y H:i'),
                'starting_cash'  => (int) $lastClosedShift->starting_cash,
                'cash_sales'     => (int) $lastClosedShift->cash_sales,
                'cash_expenses'  => (int) $lastClosedShift->cash_expenses,
                'cash_deposited' => (int) $lastClosedShift->cash_deposited,
                'expected_cash'  => (int) $lastClosedShift->expected_cash,
                'actual_cash'    => (int) $lastClosedShift->actual_cash,
                'difference'     => (int) $lastClosedShift->difference,
                'notes'          => $lastClosedShift->notes,
            ] : null,
            'unclosed_shift_warning' => $unclosedShiftWarning,
            'previous_shift_info' => $unclosedShiftWarning,
        ]);
    }

    /**
     * 2. Kasir menutup shift & rekonsiliasi uang fisik laci
     */
    public function close(Request $request)
    {
        $request->validate([
            'actual_cash' => 'required|integer|min:0',
            'notes'       => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        // Cari SATU-SATUNYA shift OPEN yang ada di toko
        $shift = \App\Models\CashierShift::where('status', 'OPEN')
            ->latest('id')
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada shift aktif di toko yang ditemukan.',
            ], 404);
        }

        // HANYA KASIR PEMILIK SHIFT (atau Admin/Owner) yang berhak menutup shiftnya
        if ($user->role === 'kasir' && $shift->user_id !== $user->id) {
            $ownerName = $shift->user?->name ?? 'Kasir sebelumnya';
            return response()->json([
                'success' => false,
                'message' => "Akses Ditolak! Shift ini milik {$ownerName}. Hanya {$ownerName} yang berhak menutup shift ini.",
            ], 403);
        }

        $endTime = now();
        $actualCash = (int) $request->actual_cash;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($shift, $user, $endTime, $actualCash, $request) {
            // Hitung final penjualan, pengeluaran & setoran
            $cashSales = (int) \App\Models\Order::where('created_at', '>=', $shift->start_time)
                ->where('created_at', '<=', $endTime)
                ->where('status', '!=', 'CANCELLED')
                ->where('payment_type', 'TUNAI')
                ->sum('total_amount');

            $cashExpenses = (int) \App\Models\CashTransaction::where('created_at', '>=', $shift->start_time)
                ->where('created_at', '<=', $endTime)
                ->where('type', 'EXPENSE')
                ->where(function ($q) use ($shift) {
                    $q->whereNull('order_id')
                      ->orWhereHas('order', fn ($oq) => $oq->where('created_at', '<', $shift->start_time));
                })
                ->sum('amount');

            $cashDeposited = (int) \App\Models\CashTransaction::where('created_at', '>=', $shift->start_time)
                ->where('created_at', '<=', $endTime)
                ->where('type', 'DEPOSIT')
                ->where('description', 'like', '%Setor ke kas besar%')
                ->sum('amount');

            $expectedCash = $shift->starting_cash + $cashSales - $cashExpenses - $cashDeposited;
            $difference = $actualCash - $expectedCash;

            // Catatan serah terima jika yang menutup shift adalah kasir berikutnya atau admin
            $noteText = $request->notes;
            if ($shift->user_id !== $user->id) {
                $handoverInfo = "Serah terima & ditutup oleh {$user->name}";
                $noteText = $noteText ? "{$noteText} ({$handoverInfo})" : $handoverInfo;
            }

            // Tutup shift
            $shift->update([
                'end_time'       => $endTime,
                'cash_sales'     => $cashSales,
                'cash_expenses'  => $cashExpenses,
                'cash_deposited' => $cashDeposited,
                'expected_cash'  => $expectedCash,
                'actual_cash'    => $actualCash,
                'difference'     => $difference,
                'notes'          => $noteText,
                'status'         => 'CLOSED',
            ]);

            // Sesuaikan saldo kas laci kasir di cash_balances agar mencerminkan uang fisik aktual yang ditinggal di laci
            $cashierBalance = \App\Models\CashBalance::where('type', 'CASHIER')->first();
            if ($cashierBalance) {
                $cashierBalance->update([
                    'balance' => $actualCash,
                    'last_transaction_at' => $endTime,
                ]);
            }

            // Kunci: Pastikan SEMUA shift OPEN di toko tertutup bersih (tidak ada yang menggantung lagi)
            \App\Models\CashierShift::where('status', 'OPEN')
                ->update([
                    'status'   => 'CLOSED',
                    'end_time' => $endTime,
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Shift berhasil ditutup! Data tersimpan di pembukuan.',
                'data'    => [
                    'id'            => $shift->id,
                    'cashier_name'  => $shift->user?->name ?? $user->name,
                    'closed_by'     => $user->name,
                    'start_time'    => $shift->start_time->format('d/m/Y H:i'),
                    'end_time'      => $shift->end_time->format('d/m/Y H:i'),
                    'starting_cash' => (int) $shift->starting_cash,
                    'cash_sales'    => (int) $cashSales,
                    'cash_expenses' => (int) $cashExpenses,
                    'cash_deposited'=> (int) $cashDeposited,
                    'expected_cash' => (int) $expectedCash,
                    'actual_cash'   => (int) $actualCash,
                    'difference'    => (int) $difference,
                    'notes'         => $shift->notes,
                ],
            ]);
        });
    }

    /**
     * 3. Riwayat shift (Bisa diakses kasir dan owner)
     */
    public function history(Request $request)
    {
        $query = \App\Models\CashierShift::with('user:id,name,role')
            ->orderByDesc('id');

        // Jika kasir biasa, hanya tampilkan miliknya. Jika owner/admin, tampilkan semua.
        if (!in_array($request->user()->role, ['owner', 'admin'])) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->date) {
            $query->whereDate('start_time', $request->date);
        }

        $shifts = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $shifts->getCollection()->map(fn($s) => [
                'id'            => $s->id,
                'cashier_name'  => $s->user?->name ?? 'Kasir',
                'start_time'    => $s->start_time->format('d/m/Y H:i'),
                'end_time'      => $s->end_time?->format('d/m/Y H:i') ?? 'Sedang Berjalan',
                'starting_cash' => (int) $s->starting_cash,
                'cash_sales'    => (int) $s->cash_sales,
                'cash_expenses' => (int) $s->cash_expenses,
                'cash_deposited'=> (int) $s->cash_deposited,
                'expected_cash' => (int) $s->expected_cash,
                'actual_cash'   => $s->actual_cash !== null ? (int) $s->actual_cash : null,
                'difference'    => (int) $s->difference,
                'status'        => $s->status,
                'notes'         => $s->notes,
            ])->values()->all(),
            'pagination' => [
                'current_page' => $shifts->currentPage(),
                'last_page'    => $shifts->lastPage(),
                'total'        => $shifts->total(),
            ],
        ]);
    }

    /**
     * 4. Riwayat setoran khusus kas besar (Laci -> Kas Besar dengan foto bukti) untuk Owner
     */
    public function depositHistory(Request $request)
    {
        $query = \App\Models\CashTransaction::with(['recordedBy:id,name', 'onBehalfOf:id,name'])
            ->where('type', 'DEPOSIT')
            ->where(function ($q) {
                $q->where('description', 'like', '%setor%')
                  ->orWhere('description', 'like', '%deposit%')
                  ->orWhereNotNull('proof_image')
                  ->orWhereNotNull('on_behalf_of');
            })
            ->where('description', 'not like', '%modal awal%')
            ->orderByDesc('created_at');

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $deposits = $query->paginate(50);

        return response()->json([
            'success' => true,
            'data'    => $deposits->getCollection()->map(fn($t) => [
                'id'               => $t->id,
                'amount'           => (int) $t->amount,
                'description'      => $t->description,
                'proof_image_url'  => $t->proof_image_url,
                'recorded_by_name' => $t->recordedBy?->name ?? 'Sistem',
                'on_behalf_of_name'=> $t->onBehalfOf?->name ?? $t->recordedBy?->name ?? 'Kasir',
                'date'             => $t->created_at->format('d/m/Y H:i'),
            ])->values()->all(),
            'pagination' => [
                'current_page' => $deposits->currentPage(),
                'last_page'    => $deposits->lastPage(),
                'total'        => $deposits->total(),
            ],
        ]);
    }
}
