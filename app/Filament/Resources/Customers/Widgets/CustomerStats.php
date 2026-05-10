<?php

namespace App\Filament\Resources\Customers\Widgets;

use App\Models\Customer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Customer', Customer::count())
                ->description('Semua pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
