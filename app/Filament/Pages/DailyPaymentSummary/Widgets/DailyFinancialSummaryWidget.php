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
        $jenis = $filter['jenis_filter'] ?? null;

        $incomeQuery = DailyPaymentReport::query();
        $expenseTxQuery = CashTransaction::where('type', 'EXPENSE');
        $purchaseQuery = CashierPurchase::query();

        if ($jenis === 'rentang' && !empty($filter['rentang_tanggal'])) {
            [$start, $end] = \App\Filament\Filters\PeriodeFilter::parseDateRange($filter['rentang_tanggal']);
            if ($start && $end) {
                $incomeQuery->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);
                $expenseTxQuery->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);
                $purchaseQuery->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end);

                $periodLabel = \Carbon\Carbon::parse($start)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($end)->format('d/m/Y');
            } else {
                $periodLabel = 'Rentang Tanggal';
            }
        } elseif ($jenis === 'tahun' && !empty($filter['tahun'])) {
            $incomeQuery->whereYear('date', $filter['tahun']);
            $expenseTxQuery->whereYear('created_at', $filter['tahun']);
            $purchaseQuery->whereYear('created_at', $filter['tahun']);
            $periodLabel = 'Tahun ' . $filter['tahun'];
        } elseif ($jenis === 'bulan' && !empty($filter['bulan'])) {
            $year = $filter['tahun_bulan'] ?? date('Y');
            $incomeQuery->whereMonth('date', $filter['bulan'])->whereYear('date', $year);
            $expenseTxQuery->whereMonth('created_at', $filter['bulan'])->whereYear('created_at', $year);
            $purchaseQuery->whereMonth('created_at', $filter['bulan'])->whereYear('created_at', $year);
            $monthName = \Carbon\Carbon::createFromDate(null, (int) $filter['bulan'], 1)->translatedFormat('M');
            $periodLabel = "{$monthName} {$year}";
        } elseif (!empty($filter['dari_tanggal']) || !empty($filter['sampai_tanggal'])) {
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
