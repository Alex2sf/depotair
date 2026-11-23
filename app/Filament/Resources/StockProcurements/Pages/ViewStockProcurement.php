<?php

namespace App\Filament\Resources\StockProcurements\Pages;

use App\Filament\Resources\StockProcurements\StockProcurementResource;
use Filament\Resources\Pages\ViewRecord;

class ViewStockProcurement extends ViewRecord
{
    protected static string $resource = StockProcurementResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
