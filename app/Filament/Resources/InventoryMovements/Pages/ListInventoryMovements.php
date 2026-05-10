<?php

namespace App\Filament\Resources\InventoryMovements\Pages;

use App\Filament\Resources\InventoryMovements\InventoryMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryMovements extends ListRecords
{
    protected static string $resource = InventoryMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            \Filament\Actions\ImportAction::make()
                ->importer(\App\Filament\Imports\InventoryMovementImporter::class)
                ->label('Import Movement')
                ->color('success')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
