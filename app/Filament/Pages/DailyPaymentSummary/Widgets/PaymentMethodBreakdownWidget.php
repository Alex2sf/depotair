<?php

namespace App\Filament\Pages\DailyPaymentSummary\Widgets;

use App\Models\DailyPaymentReport;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class PaymentMethodBreakdownWidget extends BaseWidget
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

        $query = DailyPaymentReport::query();

        if ($jenis === 'rentang' && !empty($filter['rentang_tanggal'])) {
            [$start, $end] = \App\Filament\Filters\PeriodeFilter::parseDateRange($filter['rentang_tanggal']);
            if ($start && $end) {
                $query->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);
            }
        } elseif ($jenis === 'tahun' && !empty($filter['tahun'])) {
            $query->whereYear('date', $filter['tahun']);
        } elseif ($jenis === 'bulan' && !empty($filter['bulan'])) {
            $year = $filter['tahun_bulan'] ?? date('Y');
            $query->whereMonth('date', $filter['bulan'])->whereYear('date', $year);
        } elseif (!empty($filter['dari_tanggal']) || !empty($filter['sampai_tanggal'])) {
            if (!empty($filter['dari_tanggal'])) {
                $query->whereDate('date', '>=', $filter['dari_tanggal']);
            }
            if (!empty($filter['sampai_tanggal'])) {
                $query->whereDate('date', '<=', $filter['sampai_tanggal']);
            }
        } else {
            $query->whereMonth('date', now()->month)->whereYear('date', now()->year);
        }

        $tunai = (int) (clone $query)->sum('tunai_total');
        $qris = (int) (clone $query)->sum('qris_total');
        $transfer = (int) (clone $query)->sum('transfer_total');
        $corporate = (int) (clone $query)->sum('corporate_total');
        $grandTotal = (int) (clone $query)->sum('grand_total');

        $tunaiPct = $grandTotal > 0 ? round(($tunai / $grandTotal) * 100) : 0;
        $qrisPct = $grandTotal > 0 ? round(($qris / $grandTotal) * 100) : 0;
        $transferPct = $grandTotal > 0 ? round(($transfer / $grandTotal) * 100) : 0;
        $corporatePct = $grandTotal > 0 ? round(($corporate / $grandTotal) * 100) : 0;

        return [
            Stat::make('Uang Tunai (Cash)', 'Rp ' . number_format($tunai, 0, ',', '.'))
                ->description("{$tunaiPct}% omzet • Fisik di laci toko")
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('QRIS', 'Rp ' . number_format($qris, 0, ',', '.'))
                ->description("{$qrisPct}% omzet • Rekening QRIS")
                ->descriptionIcon('heroicon-m-qr-code')
                ->color('primary'),

            Stat::make('Transfer Bank', 'Rp ' . number_format($transfer, 0, ',', '.'))
                ->description("{$transferPct}% omzet • Mutasi bank")
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('Corporate / Tempo', 'Rp ' . number_format($corporate, 0, ',', '.'))
                ->description("{$corporatePct}% omzet • Piutang berjalan")
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('gray'),
        ];
    }
}
