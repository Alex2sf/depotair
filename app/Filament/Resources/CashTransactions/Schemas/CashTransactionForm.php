<?php

namespace App\Filament\Resources\CashTransactions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
class CashTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Select::make('type')
                ->label('Jenis Transaksi')
                ->options([
                    'EXPENSE' => 'Pengeluaran (EXPENSE)',
                    'DEPOSIT' => 'Pemasukan (DEPOSIT)',
                ])
                ->required(),

            TextInput::make('amount')
                ->label('Jumlah (Rp)')
                ->numeric()
                ->prefix('Rp ')
                ->mask('999.999.999')
                ->required()
                ->minValue(0) // Unsigned in DB means positive only
                ->helperText('Masukkan angka positif. Sistem akan mencatat sesuai jenis transaksi.'),

            Textarea::make('description')
                ->label('Keterangan')
                ->required()
                ->rows(3)
                ->columnSpanFull()
                ->placeholder('Contoh: Beli pulpen, Setor tunai ke owner'),

            Select::make('recorded_by')
                ->label('Dicatat Oleh')
                ->relationship('recordedBy', 'name')
                ->default(auth()->id())
                ->required()
                ->disabled(), // Auto-filled by system

            Select::make('on_behalf_of')
                ->label('Atas Nama (Opsional)')
                ->relationship('onBehalfOf', 'name')
                ->searchable()
                ->preload()
                ->placeholder('Pilih user jika mewakili orang lain'),
            ]);
    }
}
