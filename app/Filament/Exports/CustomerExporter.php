<?php

namespace App\Filament\Exports;

use App\Models\Customer;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class CustomerExporter extends Exporter
{
    protected static ?string $model = Customer::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('name')
                ->label('Nama Lengkap'),

            ExportColumn::make('phone_number')
                ->label('Nomor HP'),

            ExportColumn::make('address')
                ->label('Alamat Lengkap'),

            ExportColumn::make('latitude')
                ->label('Latitude'),

            ExportColumn::make('longitude')
                ->label('Longitude'),

            ExportColumn::make('created_at')
                ->label('Tanggal Daftar')
                ->formatStateUsing(fn ($state) => $state->format('d/m/Y H:i')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $success = $export->successful_rows;
        $failed = $export->failed_rows;

        if ($failed === 0) {
            return "Export Customer berhasil! {$success} customer diexport.";
        }

        return "Export selesai: {$success} berhasil, {$failed} gagal.";
    }
}