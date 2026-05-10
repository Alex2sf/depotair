<?php

namespace App\Filament\Resources\Inventories\Widgets;

use App\Models\Inventory;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStats extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Item Stok', Inventory::count())
                ->description('Item dalam inventaris')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('warning'),
        ];
    }
}
