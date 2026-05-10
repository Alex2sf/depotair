<?php

namespace App\Filament\Resources\CashBalances\Schemas;

use Filament\Forms\Components\DatePicker;  // Benar untuk tanggal saja
use Filament\Forms\Components\DateTimePicker;  // Jika butuh jam juga
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Textarea;


class CashBalanceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
Select::make('type')
                ->label('Jenis Saldo')
                ->options([
                    'CASHIER' => 'Kasir (Laci)',
                    'MAIN'    => 'Owner / Utama',
                ])
                ->required()
                ->disabled(fn ($record) => $record?->exists) // ga boleh ganti setelah dibuat
                ->dehydrated(fn ($record) => !$record?->exists),

            TextInput::make('balance')
                ->label('Saldo Saat Ini')
                ->numeric()
                ->prefix('Rp ')
                ->mask('999.999.999.999')
                ->required()
                ->default(0)
                ->helperText('Diubah otomatis saat ada transaksi'),

            Textarea::make('notes')
                ->label('Catatan')
                ->rows(3)
                ->columnSpanFull()
                ->placeholder('Contoh: Saldo awal kasir hari ini'),
            ]);
    }
}
