<?php

namespace App\Filament\Resources\Inventories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->unique(ignoreRecord: true)
                ->label('Produk'),

            TextInput::make('quantity')
                ->label('Stok Sistem')
                ->numeric()
                ->required()
                ->minValue(0)
                ->default(0)
                // Saat edit tidak bisa diubah langsung: pakai tombol "Sesuaikan stok" supaya tercatat di riwayat
                ->disabledOn('edit')
                ->dehydrated(fn (string $operation) => $operation === 'create')
                ->helperText(fn (string $operation) => $operation === 'edit'
                    ? 'Ubah lewat tombol "Sesuaikan stok" di daftar Inventories supaya tercatat di Kartu Stok.'
                    : 'Stok awal. Dicatat sebagai riwayat stok masuk.'),

            TextInput::make('real_quantity')
                ->label('Stok Fisik (Opname)')
                ->numeric()
                ->required()
                ->default(0)
                ->helperText('Diisi saat opname gudang. Kosongkan jika belum pernah opname'),

            DatePicker::make('last_opname_at')
                ->label('Tanggal Terakhir Opname')
                ->maxDate(now())
                ->displayFormat('d/m/Y'),

            TextInput::make('low_stock_threshold')
                ->label('Batas Stok Rendah')
                ->numeric()
                ->default(10)
                ->required(),
        ]);
    }
}