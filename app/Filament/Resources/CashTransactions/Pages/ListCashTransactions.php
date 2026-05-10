<?php

namespace App\Filament\Resources\CashTransactions\Pages;

use App\Filament\Resources\CashTransactions\CashTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashTransactions extends ListRecords
{
    protected static string $resource = CashTransactionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CashTransactions\Widgets\CashTransactionStats::class,
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
                ->importer(\App\Filament\Imports\CashTransactionImporter::class)
                ->label('Import Cash Transaction')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
