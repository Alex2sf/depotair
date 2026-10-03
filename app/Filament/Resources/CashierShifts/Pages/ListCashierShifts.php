<?php

namespace App\Filament\Resources\CashierShifts\Pages;

use App\Filament\Resources\CashierShifts\CashierShiftResource;
use Filament\Resources\Pages\ListRecords;

class ListCashierShifts extends ListRecords
{
    protected static string $resource = CashierShiftResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()->exports([
                \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Shift-Kasir-' . date('Y-m-d'))
            ]),
        ];
    }
}
