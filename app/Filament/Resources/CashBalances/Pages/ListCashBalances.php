<?php

namespace App\Filament\Resources\CashBalances\Pages;

use App\Filament\Resources\CashBalances\CashBalanceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCashBalances extends ListRecords
{
    protected static string $resource = CashBalanceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\CashBalances\Widgets\CashBalanceStats::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\CashBalanceImporter::class)
                ->label('Import Balance')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
