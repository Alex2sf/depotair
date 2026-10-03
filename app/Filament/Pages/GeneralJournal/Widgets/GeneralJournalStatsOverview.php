<?php

namespace App\Filament\Pages\GeneralJournal\Widgets;

use App\Models\GeneralJournal;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class GeneralJournalStatsOverview extends BaseWidget
{
    #[Reactive]
    public ?array $tableFilters = null;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $filter = $this->tableFilters['transaction_date'] ?? null;
        $dariTanggal = $filter['dari_tanggal'] ?? null;
        $sampaiTanggal = $filter['sampai_tanggal'] ?? null;

        $query = GeneralJournal::query();

        if (!empty($dariTanggal) || !empty($sampaiTanggal)) {
            if (!empty($dariTanggal)) {
                $query->whereDate('transaction_date', '>=', $dariTanggal);
            }
            if (!empty($sampaiTanggal)) {
                $query->whereDate('transaction_date', '<=', $sampaiTanggal);
            }

            if (!empty($dariTanggal) && !empty($sampaiTanggal)) {
                $periodLabel = \Carbon\Carbon::parse($dariTanggal)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($sampaiTanggal)->format('d/m/Y');
            } elseif (!empty($dariTanggal)) {
                $periodLabel = 'Mulai ' . \Carbon\Carbon::parse($dariTanggal)->format('d/m/Y');
            } else {
                $periodLabel = 'Sampai ' . \Carbon\Carbon::parse($sampaiTanggal)->format('d/m/Y');
            }
        } elseif (($filter['jenis_periode'] ?? '') === 'Tahun' && !empty($filter['tahun'])) {
            $query->whereYear('transaction_date', $filter['tahun']);
            $periodLabel = 'Tahun ' . $filter['tahun'];
        } elseif (($filter['jenis_periode'] ?? '') === 'Bulan' && !empty($filter['bulan'])) {
            $month = substr($filter['bulan'], 5, 2);
            $year = substr($filter['bulan'], 0, 4);
            $query->whereMonth('transaction_date', $month)->whereYear('transaction_date', $year);
            $periodLabel = date('M Y', strtotime($filter['bulan'] . '-01'));
        } else {
            // Default: bulan ini
            $query->whereMonth('transaction_date', now()->month)->whereYear('transaction_date', now()->year);
            $periodLabel = 'Bulan Ini (' . now()->translatedFormat('M Y') . ')';
        }

        // Filter type/payment_method/category jika ada di filter table
        if (!empty($this->tableFilters['type']['value'])) {
            $query->where('type', $this->tableFilters['type']['value']);
        }
        if (!empty($this->tableFilters['payment_method']['value'])) {
            $query->where('payment_method', $this->tableFilters['payment_method']['value']);
        }

        $totalMasuk = (int) (clone $query)->sum('amount_in');
        $totalKeluar = (int) (clone $query)->sum('amount_out');
        $saldoBersih = $totalMasuk - $totalKeluar;
        $count = (int) (clone $query)->count();

        return [
            Stat::make('Total Pemasukan (Debit)', 'Rp ' . number_format($totalMasuk, 0, ',', '.'))
                ->description('Uang masuk ' . $periodLabel)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Pengeluaran (Kredit)', 'Rp ' . number_format($totalKeluar, 0, ',', '.'))
                ->description('Uang keluar ' . $periodLabel)
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Arus Kas Bersih', 'Rp ' . number_format($saldoBersih, 0, ',', '.'))
                ->description($saldoBersih >= 0 ? 'Surplus (Masuk > Keluar)' : 'Defisit (Keluar > Masuk)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($saldoBersih >= 0 ? 'primary' : 'danger'),

            Stat::make('Total Transaksi', number_format($count) . ' Baris')
                ->description('Mutasi jurnal tercatat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),
        ];
    }
}
