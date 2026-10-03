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
        return 4;
    }

    protected function getStats(): array
    {
        $filter = $this->tableFilters['date'] ?? null;
        $dariTanggal = $filter['dari_tanggal'] ?? null;
        $sampaiTanggal = $filter['sampai_tanggal'] ?? null;

        $incomeQuery = DailyPaymentReport::query();
        $expenseTxQuery = CashTransaction::where('type', 'EXPENSE');
        $purchaseQuery = CashierPurchase::query();

        if (!empty($dariTanggal) || !empty($sampaiTanggal)) {
            if (!empty($dariTanggal)) {
                $incomeQuery->whereDate('date', '>=', $dariTanggal);
                $expenseTxQuery->whereDate('created_at', '>=', $dariTanggal);
                $purchaseQuery->whereDate('created_at', '>=', $dariTanggal);
            }
            if (!empty($sampaiTanggal)) {
                $incomeQuery->whereDate('date', '<=', $sampaiTanggal);
                $expenseTxQuery->whereDate('created_at', '<=', $sampaiTanggal);
                $purchaseQuery->whereDate('created_at', '<=', $sampaiTanggal);
            }

            if (!empty($dariTanggal) && !empty($sampaiTanggal)) {
                $periodLabel = \Carbon\Carbon::parse($dariTanggal)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($sampaiTanggal)->format('d/m/Y');
            } elseif (!empty($dariTanggal)) {
                $periodLabel = 'Mulai ' . \Carbon\Carbon::parse($dariTanggal)->format('d/m/Y');
            } else {
                $periodLabel = 'Sampai ' . \Carbon\Carbon::parse($sampaiTanggal)->format('d/m/Y');
            }
        } elseif (($filter['jenis_periode'] ?? '') === 'Tahun' && !empty($filter['tahun'])) {
            $incomeQuery->whereYear('date', $filter['tahun']);
            $expenseTxQuery->whereYear('created_at', $filter['tahun']);
            $purchaseQuery->whereYear('created_at', $filter['tahun']);
            $periodLabel = 'Tahun ' . $filter['tahun'];
        } elseif (($filter['jenis_periode'] ?? '') === 'Bulan' && !empty($filter['bulan'])) {
            $month = substr($filter['bulan'], 5, 2);
            $year = substr($filter['bulan'], 0, 4);
            $incomeQuery->whereMonth('date', $month)->whereYear('date', $year);
            $expenseTxQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $purchaseQuery->whereMonth('created_at', $month)->whereYear('created_at', $year);
            $periodLabel = date('M Y', strtotime($filter['bulan'] . '-01'));
        } else {
            // Default: bulan ini
            $incomeQuery->whereMonth('date', now()->month)->whereYear('date', now()->year);
            $expenseTxQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $purchaseQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
            $periodLabel = 'Bulan Ini (' . now()->translatedFormat('M Y') . ')';
        }

        $totalPemasukan = (int) $incomeQuery->sum('grand_total');
        $totalPending = (int) (clone $incomeQuery)->sum('pending_total');
        $totalPengeluaran = (int) $expenseTxQuery->sum('amount') + (int) $purchaseQuery->sum('amount');
        $kasBersih = $totalPemasukan - $totalPengeluaran;

        return [
            Stat::make('Pemasukan Selesai', 'Rp ' . number_format($totalPemasukan, 0, ',', '.'))
                ->description('Omzet sah pesanan ' . $periodLabel)
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Siap / Diantar (Pending)', 'Rp ' . number_format($totalPending, 0, ',', '.'))
                ->description('Pesanan belum selesai diantar')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),

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
