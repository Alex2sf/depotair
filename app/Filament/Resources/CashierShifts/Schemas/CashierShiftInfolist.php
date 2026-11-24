<?php

namespace App\Filament\Resources\CashierShifts\Schemas;

use App\Models\CashierShift;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CashierShiftInfolist
{
    /** @var array<int, array{shift_breakdown_md: string, calendar_comparison_md: string}> */
    private static array $breakdownCache = [];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Shift')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('user.name')
                                ->label('Kasir Bertugas')
                                ->weight('bold')
                                ->icon('heroicon-o-user'),

                            TextEntry::make('status')
                                ->label('Status Shift')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'OPEN' => 'success',
                                    'CLOSED' => 'gray',
                                    default => 'secondary',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'OPEN' => 'Sedang Aktif',
                                    'CLOSED' => 'Sudah Tutup',
                                    default => $state,
                                }),

                            TextEntry::make('start_time')
                                ->label('Waktu Buka Shift')
                                ->dateTime('l, d F Y - H:i')
                                ->icon('heroicon-o-arrow-right-on-rectangle'),

                            TextEntry::make('end_time')
                                ->label('Waktu Tutup Shift')
                                ->dateTime('l, d F Y - H:i')
                                ->placeholder('Shift Masih Dibuka')
                                ->icon('heroicon-o-arrow-left-on-rectangle'),
                        ]),
                    ]),

                Section::make('Rekonsiliasi Kas Laci Kasir')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('starting_cash')
                                ->label('Uang di Laci (Awal)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('lg')
                                ->color('info'),

                            TextEntry::make('cash_sales')
                                ->label('Penjualan Tunai (+)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('lg')
                                ->color('success'),

                            TextEntry::make('cash_expenses')
                                ->label('Belanja Kasir (-)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('lg')
                                ->color('danger'),

                            TextEntry::make('cash_deposited')
                                ->label('Setor Kas Besar (-)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                ->size('lg')
                                ->color('warning'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('expected_cash')
                                ->label('Uang Seharusnya (Sistem)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('xl')
                                ->weight('bold'),

                            TextEntry::make('actual_cash')
                                ->label('Uang Fisik Aktual (Kasir)')
                                ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum Input')
                                ->size('xl')
                                ->weight('bold')
                                ->color('primary'),

                            TextEntry::make('difference')
                                ->label('Status Selisih')
                                ->badge()
                                ->size('lg')
                                ->color(fn ($state) => match (true) {
                                    $state == 0 => 'success',
                                    $state < 0 => 'danger',
                                    default => 'warning',
                                })
                                ->formatStateUsing(fn ($state, $record) => match (true) {
                                    $record->status === 'OPEN' => 'Shift Masih Aktif',
                                    $state == 0 => 'Pas / Klop (Rp 0)',
                                    $state < 0 => 'Tekor -Rp ' . number_format(abs($state), 0, ',', '.'),
                                    default => 'Lebih +Rp ' . number_format($state, 0, ',', '.'),
                                }),
                        ]),
                    ]),

                Section::make('Rekonsiliasi Penjualan Tunai (Shift vs Laporan Pembayaran)')
                    ->description('Penjelasan mengapa angka Penjualan Tunai di Shift Kasir bisa berbeda dengan Laporan Pembayaran harian (00:00–23:59).')
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('shift_daily_breakdown')
                                ->label('Rincian Masuk Laci per Tanggal (Rentang Shift Ini)')
                                ->getStateUsing(fn (CashierShift $record): string => self::shiftCashBreakdown($record)['shift_breakdown_md'])
                                ->markdown(),

                            TextEntry::make('calendar_comparison')
                                ->label('Pembanding Laporan Pembayaran (Kalender 00:00–23:59)')
                                ->getStateUsing(fn (CashierShift $record): string => self::shiftCashBreakdown($record)['calendar_comparison_md'])
                                ->markdown(),
                        ]),
                    ]),

                Section::make('Catatan Kasir')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan saat Tutup Shift')
                            ->placeholder('Tidak ada catatan dari kasir.')
                            ->markdown(),
                    ]),
            ]);
    }

    /**
     * Hitung rincian transaksi tunai lintas hari pada rentang shift ini
     * dibandingkan dengan angka kalender (00:00-23:59) di Laporan Pembayaran.
     *
     * @return array{shift_breakdown_md: string, calendar_comparison_md: string}
     */
    private static function shiftCashBreakdown(CashierShift $record): array
    {
        if (isset(self::$breakdownCache[$record->id])) {
            return self::$breakdownCache[$record->id];
        }

        $from = Carbon::parse($record->start_time);
        $to = $record->end_time ? Carbon::parse($record->end_time) : now();
        $refDate = $to->copy()->startOfDay();
        $refDateStr = $refDate->toDateString();

        // 1. Pecah transaksi masuk laci di rentang shift ini berdasarkan tanggal kalender
        $byDate = DB::table('cash_transactions')
            ->where('cash_type', 'CASHIER')
            ->where('type', 'DEPOSIT')
            ->where('description', 'not like', 'Setor ke kas besar%')
            ->where('created_at', '>=', $from)
            ->where('created_at', '<=', $to)
            ->selectRaw('DATE(created_at) as dt, COUNT(*) as cnt, SUM(amount) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('dt')
            ->get();

        $shiftTotal = 0;
        $lines = [];
        foreach ($byDate as $row) {
            $amt = (int) $row->total;
            $shiftTotal += $amt;
            $dtLabel = Carbon::parse($row->dt)->translatedFormat('d M Y');
            $tag = ($row->dt !== $refDateStr) ? ' *(lintas hari dari malam sebelumnya)*' : '';
            $lines[] = "- **{$dtLabel}:** Rp " . number_format($amt, 0, ',', '.') . " ({$row->cnt} trx){$tag}";
        }
        if (empty($lines)) {
            $lines[] = '- Belum ada transaksi tunai masuk di shift ini.';
        }
        $lines[] = '';
        $lines[] = '**Total Asli Shift Ini (patokan cek laci):** **Rp ' . number_format($shiftTotal, 0, ',', '.') . '**';

        // 2. Pembanding Laporan Pembayaran di tanggal tutup shift (00:00-23:59)
        $calendarTotal = (int) DB::table('orders')
            ->where('status', 'COMPLETE')
            ->where('payment_type', 'TUNAI')
            ->whereDate('created_at', $refDateStr)
            ->sum('total_amount');

        // 3. Transaksi tunai di tanggal yang sama SETELAH shift ditutup (masuk shift berikutnya)
        $afterClose = DB::table('cash_transactions')
            ->where('cash_type', 'CASHIER')
            ->where('type', 'DEPOSIT')
            ->where('description', 'not like', 'Setor ke kas besar%')
            ->whereDate('created_at', $refDateStr)
            ->where('created_at', '>', $to)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total')
            ->first();

        // 4. Nota CANCELLED di rentang shift ini yang sempat masuk DEPOSIT lalu keluar EXPENSE
        $cancelled = DB::table('cash_transactions as ct')
            ->join('orders as o', 'o.id', '=', 'ct.order_id')
            ->where('ct.cash_type', 'CASHIER')
            ->where('ct.type', 'DEPOSIT')
            ->where('o.status', 'CANCELLED')
            ->where('ct.created_at', '>=', $from)
            ->where('ct.created_at', '<=', $to)
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(ct.amount), 0) as total')
            ->first();

        $refLabel = $refDate->translatedFormat('d M Y');
        $afterCnt = (int) ($afterClose->cnt ?? 0);
        $afterTot = (int) ($afterClose->total ?? 0);
        $cancelCnt = (int) ($cancelled->cnt ?? 0);
        $cancelTot = (int) ($cancelled->total ?? 0);

        $compLines = [
            "- **Laporan Pembayaran ({$refLabel}, 00:00–23:59):** Rp " . number_format($calendarTotal, 0, ',', '.'),
            '- **Penjualan Malam Setelah Shift Tutup (masuk shift berikutnya):** Rp ' . number_format($afterTot, 0, ',', '.') . " ({$afterCnt} trx)",
            '- **Nota Batal (Refund Tunai) di Shift Ini:** Rp ' . number_format($cancelTot, 0, ',', '.') . " ({$cancelCnt} nota)",
            '',
            '_Catatan: Untuk cek fisik uang laci kasir, selalu pakai **Total Asli Shift** (dari jam buka s/d tutup shift)._',
        ];

        return self::$breakdownCache[$record->id] = [
            'shift_breakdown_md' => implode("\n", $lines),
            'calendar_comparison_md' => implode("\n", $compLines),
        ];
    }
}
