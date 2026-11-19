<?php

namespace App\Support;

use App\Models\GeneralJournal;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Menyusun mutasi sistem dengan kolom yang sama seperti CSV mutasi BCA
 * (Tanggal; Jam; Keterangan; Jumlah; CR/DB; Saldo) supaya owner bisa
 * membandingkan berdampingan per rekening.
 */
class BcaStatementExport
{
    public const ACCOUNTS = [
        'QRIS'     => 'QRIS (bandingkan dengan file QRIS BCA)',
        'SETORAN'  => 'Setoran ke Kas Besar (bandingkan dengan SETORAN VIA CDM)',
        'TRANSFER' => 'Transfer Pelanggan (bandingkan dengan TRSF E-BANKING CR)',
        'LACI'     => 'Laci Kasir (uang tunai fisik)',
    ];

    /** @return array<int, array{date:string,time:string,description:string,amount:int,direction:string,balance:int}> */
    public static function rows(string $account, CarbonInterface $from, CarbonInterface $to): array
    {
        $range = [$from->copy()->startOfDay(), $to->copy()->endOfDay()];

        $entries = match ($account) {
            'QRIS', 'TRANSFER' => DB::table('orders')
                ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
                ->where('orders.status', 'COMPLETE')
                ->where('orders.payment_type', $account)
                ->whereBetween('orders.created_at', $range)
                ->orderBy('orders.created_at')->orderBy('orders.id')
                ->get(['orders.created_at as at', 'orders.total_amount as amount', 'orders.order_number', 'customers.name as customer'])
                ->map(fn ($r) => [
                    'at' => $r->at,
                    'description' => "{$account} #{$r->order_number} " . ($r->customer ?? 'Pelanggan'),
                    'in' => (int) $r->amount,
                    'out' => 0,
                ]),

            'SETORAN' => DB::table('cash_transactions')
                ->where('type', 'DEPOSIT')
                ->where('description', 'like', 'Setor ke kas besar%')
                ->whereBetween('created_at', $range)
                ->orderBy('created_at')->orderBy('id')
                ->get(['created_at as at', 'amount', 'description'])
                ->map(fn ($r) => [
                    'at' => $r->at,
                    'description' => $r->description,
                    'in' => (int) $r->amount,
                    'out' => 0,
                ]),

            'LACI' => GeneralJournal::query()
                ->where('account', 'LACI')
                ->whereBetween('transaction_date', $range)
                ->orderBy('transaction_date')->orderBy('id')
                ->get()
                ->map(fn ($r) => [
                    'at' => $r->transaction_date,
                    'description' => trim(($r->ref_number ? "{$r->ref_number} " : '') . $r->description),
                    'in' => (int) $r->amount_in,
                    'out' => (int) $r->amount_out,
                ]),

            default => throw new \InvalidArgumentException("Akun tidak dikenal: {$account}"),
        };

        // Saldo berjalan dihitung dari awal periode export (0), seperti kolom Saldo di mutasi BCA
        $balance = 0;
        $rows = [];
        foreach ($entries as $e) {
            $at = \Illuminate\Support\Carbon::parse($e['at']);
            $credit = $e['in'] > 0;
            $amount = $credit ? $e['in'] : $e['out'];
            $balance += $credit ? $amount : -$amount;
            $rows[] = [
                'date' => $at->format('d/m/Y'),
                'time' => $at->format('H:i'),
                'description' => $e['description'],
                'amount' => $amount,
                'direction' => $credit ? 'CR' : 'DB',
                'balance' => $balance,
            ];
        }

        return $rows;
    }

    public static function toCsv(array $rows): string
    {
        // Pemisah ';' dan angka tanpa titik ribuan, sama seperti file mutasi BCA, agar langsung terbaca Excel
        $clean = fn (string $v) => str_replace([';', "\r", "\n"], [',', ' ', ' '], $v);
        $csv = "Tanggal;Jam;Keterangan;Jumlah;CR/DB;Saldo\n";
        $in = $out = 0;
        foreach ($rows as $r) {
            $csv .= "'{$r['date']};{$r['time']};{$clean($r['description'])};{$r['amount']};{$r['direction']};{$r['balance']}\n";
            $r['direction'] === 'CR' ? $in += $r['amount'] : $out += $r['amount'];
        }
        $csv .= "Kredit;=;{$in}\nDebet;=;{$out}\nSaldo Akhir;=;" . ($in - $out) . "\n";

        return $csv;
    }
}
