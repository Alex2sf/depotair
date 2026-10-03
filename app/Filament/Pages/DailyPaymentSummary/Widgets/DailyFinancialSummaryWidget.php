<?php

namespace App\Filament\Pages\DailyPaymentSummary\Widgets;

use App\Models\CashTransaction;
use App\Models\CashierPurchase;
use App\Models\DailyPaymentReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class DailyFinancialSummaryWidget extends BaseWidget
{
    #[Reactive]
    public ?array $tableFilters = null;

    protected ?string $pollingInterval = null;

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $filter = $this->tableFilters['date'] ?? null;
        $jenis = $filter['jenis_periode'] ?? 'Bulan';

        $incomeQuery = DailyPaymentReport::query();
        $expenseTxQuery = CashTransaction::where('type', 'EXPENSE');
        $purchaseQuery = CashierPurchase::query();

        if ($jenis === 'Tahun' && !empty($filter['tahun'])) {
            $incomeQuery->whereYear('date', $filter['tahun']);
            $expenseTxQuery->whereYear('created_at', $filter['tahun']);
            $purchaseQuery->whereYear('created_at', $filter['tahun']);
            $periodLabel = 'Tahun ' . $filter['tahun'];
        } elseif ($jenis === 'Bulan' && !empty($filter['bulan'])) {
            $month = substr($filter['bulan'], 5, 2);
            $year = substr($filter['bulan'], 0, 4);
            $incomeQuery->whereMonth('date', $month)->whereYear('date', $year);
            $expenseTxQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $purchaseQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $periodLabel = date('M Y', strtotime($filter['bulan'] . '-01'));
        } elseif ($jenis === 'Tanggal') {
            if (!empty($filter['dari_tanggal'])) {
                $incomeQuery->whereDate('date', '>=', $filter['dari_tanggal']);
                $expenseTxQuery->whereDate('created_at', '>=', $filter['dari_tanggal']);
                $purchaseQuery->whereDate('created_at', '>=', $filter['dari_tanggal']);
            }
            if (!empty($filter['sampai_tanggal'])) {
                $incomeQuery->whereDate('date', '<=', $filter['sampai_tanggal']);
                $expenseTxQuery->whereDate('created_at', '<=', $filter['sampai_tanggal']);
                $purchaseQuery->whereDate('created_at', '<=', $filter['sampai_tanggal']);
            }
            $periodLabel = 'Rentang Tanggal';
        } else {
            // Default: bulan ini
            $incomeQuery->whereMonth('date', now()->month)->whereYear('date', now()->year);
            $expenseTxQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $purchaseQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $periodLabel = 'Bulan Ini (' . now()->translatedFormat('M Y') . ')';
        }

        $totalPemasukan = (int) $incomeQuery->sum('grand_total');
        $totalPengeluaran = (int) $expenseTxQuery->sum('amount') + (int) $purchaseQuery->sum('amount');
        $kasBersih = $totalPemasukan - $totalPengeluaran;

        return [
            Stat::make('Total Pemasukan (Omzet)', 'Rp ' . number_format($totalPemasukan, 0, ',', '.'))
                ->description('Total penjualan ' . $periodLabel)
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Pengeluaran', 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'))
                ->description('Belanja kasir & operasional kas')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),

            Stat::make('Arus Kas Bersih (Laba)', 'Rp ' . number_format($kasBersih, 0, ',', '.'))
                ->description($kasBersih >= 0 ? 'Surplus (Pemasukan > Pengeluaran)' : 'Defisit (Pengeluaran > Pemasukan)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color($kasBersih >= 0 ? 'primary' : 'danger'),
        ];
    }
}
