<?php

namespace App\Filament\Resources\CashBalances\Widgets;

use App\Models\CashBalance;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashBalanceStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Akun Kas', CashBalance::count())
                ->description('Akun saldo aktif')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
