<?php

namespace App\Filament\Imports;

use App\Models\Customer;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class CustomerImporter extends Importer
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'max:255']),

            ImportColumn::make('phone_number')
                ->requiredMapping()
                ->rules(['required', 'unique:customers,phone_number', 'max:20']),

            ImportColumn::make('address')
                ->rules(['nullable']),

            ImportColumn::make('latitude')
                ->castStateUsing(fn ($state) => $state ? str_replace(',', '.', $state) : null)
                ->numeric()
                ->rules(['nullable', 'numeric']),

            ImportColumn::make('longitude')
                ->castStateUsing(fn ($state) => $state ? str_replace(',', '.', $state) : null)
                ->numeric()
                ->rules(['nullable', 'numeric']),
        ];
    }

    public function resolveRecord(): ?Customer
    {
        // Skip kalau nomor HP sudah ada (anti-duplikat)
        $existing = Customer::where('phone_number', $this->data['phone_number'])->first();
        if ($existing) {
            return null; // Skip row ini
        }

        return new Customer();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $success = $import->successful_rows;
        $failed = $import->failed_rows;

        if ($failed === 0) {
            return "Import Customer berhasil! {$success} customer baru ditambahkan.";
        }

        return "Import selesai: {$success} berhasil, {$failed} gagal (mungkin duplikat nomor HP).";
    }
}