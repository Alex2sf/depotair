<?php

namespace App\Filament\Resources\ProductTypes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Tipe / Kategori')
                    ->placeholder('Misal: Galon Kosong, Aksesoris, Air Mineral')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($set, $state, $operation) {
                        if ($operation === 'create') {
                            $set('code', strtoupper(\Illuminate\Support\Str::slug($state, '_')));
                        }
                    }),

                TextInput::make('code')
                    ->label('Kode Tipe (Unik)')
                    ->placeholder('REFILL, CONSUMABLE, AKSESORIS, dll')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->helperText('Digunakan sebagai identitas sistem di database dan API.'),

                \Filament\Forms\Components\Select::make('color')
                    ->label('Warna Label / Badge')
                    ->options([
                        'primary' => 'Amber / Orange (Primary)',
                        'info' => 'Biru (Info)',
                        'success' => 'Hijau (Success)',
                        'warning' => 'Kuning (Warning)',
                        'danger' => 'Merah (Danger)',
                        'gray' => 'Abu-abu (Gray)',
                    ])
                    ->default('primary')
                    ->required(),

                TextInput::make('icon')
                    ->label('Heroicon (Opsional)')
                    ->placeholder('heroicon-o-tag')
                    ->helperText('Contoh: heroicon-o-tag, heroicon-o-archive-box, heroicon-o-sparkles'),

                Textarea::make('description')
                    ->label('Keterangan / Catatan')
                    ->rows(3)
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true)
                    ->required(),
            ]);
    }
}
