<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $today = Carbon::today();

        // 1. Pendapatan Hari Ini
        $incomeToday = Order::whereDate('created_at', $today)
            ->where('status', '!=', 'CANCEL')
            ->sum('total_amount');

        // 2. Total Order Hari Ini
        $ordersToday = Order::whereDate('created_at', $today)->count();

        // 3. Pesanan Siap / Diantar (Active)
        $activeOrders = Order::whereIn('status', ['READY', 'ON_DELIVERY'])->count();

        return [
            Stat::make('Pendapatan Hari Ini', 'Rp ' . number_format($incomeToday, 0, ',', '.'))
                ->description('Total omzet hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Order Hari Ini', $ordersToday)
                ->description('Total transaksi masuk')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Dalam Proses', $activeOrders)
                ->description('Siap / Sedang Diantar')
                ->descriptionIcon('heroicon-m-truck')
                ->color('warning'),
        ];
    }
}
