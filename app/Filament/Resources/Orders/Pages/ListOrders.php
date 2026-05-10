<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;
use pxlrbt\FilamentExcel\Columns\Column;

use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Actions\Action; // KURANG INI!
use App\Filament\Imports\OrderImporter;
use Filament\Actions\ImportAction;
use Filament\Actions\CreateAction;
class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Orders\Widgets\OrderStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()->exports([
                \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()
            ]),
            ImportAction::make()
                ->importer(OrderImporter::class)
                ->label('Import Order dari CSV')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),


        ];
    }
}
