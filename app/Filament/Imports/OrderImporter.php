<?php

namespace App\Filament\Imports;

use App\Models\Order;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class OrderImporter extends Importer
{
    protected static ?string $model = Order::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('order_number')
                ->rules(['max:255']),
            ImportColumn::make('customer')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('staff')
                ->relationship(),
            ImportColumn::make('courier')
                ->relationship(),
            ImportColumn::make('status')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('order_type')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('payment_type')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('delivery_address'),
            ImportColumn::make('address_link'),
            ImportColumn::make('delivery_scheduled_at')
                ->rules(['datetime']),
            ImportColumn::make('latitude')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('longitude')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('subtotal')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('delivery_fee')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('additional_fee')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('total_amount')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('notes'),
            ImportColumn::make('delivery_time')
                ->rules(['datetime']),
            ImportColumn::make('completed_time')
                ->rules(['datetime']),
        ];
    }

    public function resolveRecord(): Order
    {
        return new Order();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your order import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
