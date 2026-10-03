<?php

namespace App\Filament\Resources\CashierPurchases\Pages;

use App\Filament\Resources\CashierPurchases\CashierPurchaseResource;
use Filament\Resources\Pages\ListRecords;

class ListCashierPurchases extends ListRecords
{
    protected static string $resource = CashierPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()->exports([
                \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Belanja-Kasir-' . date('Y-m-d'))
            ]),
        ];
    }
}
