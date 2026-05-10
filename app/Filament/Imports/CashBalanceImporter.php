<?php

namespace App\Filament\Imports;

use App\Models\CashBalance;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class CashBalanceImporter extends Importer
{
    protected static ?string $model = CashBalance::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('type')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('balance')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('last_transaction_at')
                ->rules(['datetime']),
        ];
    }

    public function resolveRecord(): CashBalance
    {
        return new CashBalance();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your cash balance import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
