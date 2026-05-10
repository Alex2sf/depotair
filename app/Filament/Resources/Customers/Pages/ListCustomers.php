<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\CreateAction;

use Filament\Actions\ImportAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Customers\Widgets\CustomerStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \pxlrbt\FilamentExcel\Actions\Pages\ExportAction::make()->exports([
                \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()
            ]),
            CreateAction::make(),

            ImportAction::make()
                ->importer(\App\Filament\Imports\CustomerImporter::class)
                ->label('Import Customer')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}