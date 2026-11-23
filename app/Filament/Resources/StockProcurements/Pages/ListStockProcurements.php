<?php

namespace App\Filament\Resources\StockProcurements\Pages;

use App\Filament\Resources\StockProcurements\StockProcurementResource;
use Filament\Resources\Pages\ListRecords;

class ListStockProcurements extends ListRecords
{
    protected static string $resource = StockProcurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()->exports([
                \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Pengajuan-Barang-' . date('Y-m-d')),
            ]),
        ];
    }
}
