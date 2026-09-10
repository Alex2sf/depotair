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

        // Cari shift OPEN milik user ini
        $activeShift = \App\Models\CashierShift::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        // Cari saldo laci kasir saat ini
        $cashierBalance = (int) (\App\Models\CashBalance::where('type', 'CASHIER')->value('balance') ?? 0);

        if (!$activeShift) {
            // Jika ada shift OPEN dari kasir lain, kasir lain mungkin belum tutup shift atau serah terima
            $otherOpenShift = \App\Models\CashierShift::where('status', 'OPEN')
                ->where('user_id', '!=', $user->id)
                ->with('user:id,name')
                ->latest('id')
                ->first();

            // Auto-buka shift untuk kasir yang baru login dengan modal awal = saldo laci sekarang
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
        }

        // Hitung akumulasi real-time selama shift ini berjalan
        $shiftStart = $activeShift->start_time;

        // Total penjualan TUNAI order selesai / on delivery selama shift
        $cashSales = (int) \App\Models\Order::where('created_at', '>=', $shiftStart)
            ->whereIn('status', ['COMPLETE', 'ON_DELIVERY'])
            ->where('payment_type', 'TUNAI')
            ->sum('total_amount');

        // Total pengeluaran kas laci selama shift
        $cashExpenses = (int) \App\Models\CashTransaction::where('created_at', '>=', $shiftStart)
            ->where('type', 'EXPENSE')
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

        return response()->json([
            'success' => true,
            'shift'   => [
                'id'             => $activeShift->id,
                'cashier_name'   => $user->name,
                'start_time'     => $activeShift->start_time->format('d/m/Y H:i'),
                'starting_cash'  => (int) $activeShift->starting_cash,
                'cash_sales'     => (int) $cashSales,
                'cash_expenses'  => (int) $cashExpenses,
                'cash_deposited' => (int) $cashDeposited,
                'expected_cash'  => (int) $expectedCash,
                'current_drawer_balance' => (int) $cashierBalance,
                'status'         => $activeShift->status,
            ],
            'previous_shift_info' => isset($otherOpenShift) ? [
                'other_user_name' => $otherOpenShift->user?->name,
                'opened_at'       => $otherOpenShift->start_time->format('d/m/Y H:i'),
            ] : null,
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

        $shift = \App\Models\CashierShift::where('user_id', $user->id)
            ->where('status', 'OPEN')
            ->latest('id')
            ->first();

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada shift aktif yang ditemukan.',
            ], 404);
        }

        $endTime = now();
        $actualCash = (int) $request->actual_cash;

        // Hitung final penjualan, pengeluaran & setoran
        $cashSales = (int) \App\Models\Order::where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $endTime)
            ->whereIn('status', ['COMPLETE', 'ON_DELIVERY'])
            ->where('payment_type', 'TUNAI')
            ->sum('total_amount');

        $cashExpenses = (int) \App\Models\CashTransaction::where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $endTime)
            ->where('type', 'EXPENSE')
            ->sum('amount');

        $cashDeposited = (int) \App\Models\CashTransaction::where('created_at', '>=', $shift->start_time)
            ->where('created_at', '<=', $endTime)
            ->where('type', 'DEPOSIT')
            ->where('description', 'like', '%Setor ke kas besar%')
            ->sum('amount');

        $expectedCash = $shift->starting_cash + $cashSales - $cashExpenses - $cashDeposited;
        $difference = $actualCash - $expectedCash;

        // Tutup shift
        $shift->update([
            'end_time'       => $endTime,
            'cash_sales'     => $cashSales,
            'cash_expenses'  => $cashExpenses,
            'cash_deposited' => $cashDeposited,
            'expected_cash'  => $expectedCash,
            'actual_cash'    => $actualCash,
            'difference'     => $difference,
            'notes'          => $request->notes,
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

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil ditutup! Data tersimpan di pembukuan.',
            'data'    => [
                'id'            => $shift->id,
                'cashier_name'  => $user->name,
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
            'data'    => $shifts->map(fn($s) => [
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
            ]),
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
            ->where('description', 'like', '%Setor ke kas besar%')
            ->orderByDesc('created_at');

        if ($request->date) {
            $query->whereDate('created_at', $request->date);
        }

        $deposits = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $deposits->map(fn($t) => [
                'id'               => $t->id,
                'amount'           => (int) $t->amount,
                'description'      => $t->description,
                'proof_image_url'  => $t->proof_image_url,
                'recorded_by_name' => $t->recordedBy?->name ?? 'Sistem',
                'on_behalf_of_name'=> $t->onBehalfOf?->name ?? $t->recordedBy?->name ?? 'Kasir',
                'date'             => $t->created_at->format('d/m/Y H:i'),
            ]),
            'pagination' => [
                'current_page' => $deposits->currentPage(),
                'last_page'    => $deposits->lastPage(),
                'total'        => $deposits->total(),
            ],
        ]);
    }
}
