<?php

namespace App\Filament\Resources\CashTransactions\Widgets;

use App\Models\CashTransaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashTransactionStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Transaksi', CashTransaction::count())
                ->description('Semua riwayat transaksi kas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
