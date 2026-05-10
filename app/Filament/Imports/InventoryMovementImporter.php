<?php

namespace App\Filament\Imports;

use App\Models\InventoryMovement;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class InventoryMovementImporter extends Importer
{
    protected static ?string $model = InventoryMovement::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('product_id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('order')
                ->relationship(),
            ImportColumn::make('user')
                ->relationship(),
            ImportColumn::make('quantity_before')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('quantity_change')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('quantity_after')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('reason')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('notes'),
        ];
    }

    public function resolveRecord(): InventoryMovement
    {
        return new InventoryMovement();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your inventory movement import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
