<?php

namespace App\Filament\Imports;

use App\Models\CashTransaction;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CashTransactionImporter extends Importer
{
    protected static ?string $model = CashTransaction::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('type')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('amount')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('description')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('recorded_by')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('on_behalf_of')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('order')
                ->relationship(),
        ];
    }

    public function resolveRecord(): CashTransaction
    {
        return new CashTransaction();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your cash transaction import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
