<?php

namespace App\Filament\Resources\CashierPurchases\Pages;

use App\Filament\Resources\CashierPurchases\CashierPurchaseResource;
use Filament\Resources\Pages\ViewRecord;

class ViewCashierPurchase extends ViewRecord
{
    protected static string $resource = CashierPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
