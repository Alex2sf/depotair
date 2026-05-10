<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Inventories\Widgets\InventoryStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()->exports([
                \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()
            ]),
            CreateAction::make(),

            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\InventoryImporter::class)
                ->label('Import Inventory')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
