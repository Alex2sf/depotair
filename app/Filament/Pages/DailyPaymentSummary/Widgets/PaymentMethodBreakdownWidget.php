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
        $dariTanggal = $filter['dari_tanggal'] ?? null;
        $sampaiTanggal = $filter['sampai_tanggal'] ?? null;

        $query = DailyPaymentReport::query();

        if (!empty($dariTanggal) || !empty($sampaiTanggal)) {
            if (!empty($dariTanggal)) {
                $query->whereDate('date', '>=', $dariTanggal);
            }
            if (!empty($sampaiTanggal)) {
                $query->whereDate('date', '<=', $sampaiTanggal);
            }
        } elseif (($filter['jenis_periode'] ?? '') === 'Tahun' && !empty($filter['tahun'])) {
            $query->whereYear('date', $filter['tahun']);
        } elseif (($filter['jenis_periode'] ?? '') === 'Bulan' && !empty($filter['bulan'])) {
            $month = substr($filter['bulan'], 5, 2);
            $year = substr($filter['bulan'], 0, 4);
            $query->whereMonth('date', $month)->whereYear('date', $year);
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
