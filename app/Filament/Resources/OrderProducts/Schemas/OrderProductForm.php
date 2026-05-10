<?php

namespace App\Filament\Resources\OrderProducts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;

class OrderProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            Select::make('order_id')
                ->label('Nomor Order')
                ->relationship(
                    name: 'order',
                    titleAttribute: 'order_number', // cukup ini!
                    modifyQueryUsing: fn ($query) => $query->latest('created_at')
                )
                ->searchable()
                ->preload()
                ->required(),

            Select::make('product_id')
                ->label('Produk')
                ->relationship('product', 'name', fn ($query) => $query->where('is_enabled', true))
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, $set) {
                    if ($state) {
                        $product = \App\Models\Product::find($state);
                        $set('price_at_sale', $product?->price ?? 0);
                        $set('cogs_at_sale', $product?->cogs ?? 0);
                    }
                }),

            TextInput::make('quantity')
                ->label('Jumlah')
                ->numeric()
                ->required()
                ->minValue(1)
                ->default(1)
                ->reactive()
                ->afterStateUpdated(fn ($state, $get, $set) => 
                    $set('subtotal', ($state ?? 0) * ($get('price_at_sale') ?? 0))
                ),

            TextInput::make('price_at_sale')
                ->label('Harga Jual Saat Ini')
                ->numeric()
                ->prefix('Rp ')
                ->disabled()
                ->dehydrated(),

            TextInput::make('cogs_at_sale')
                ->label('HPP Saat Ini')
                ->numeric()
                ->prefix('Rp ')
                ->disabled()
                ->dehydrated()
                ->helperText('Harga pokok saat transaksi'),

            TextInput::make('subtotal')
                ->label('Subtotal')
                ->numeric()
                ->prefix('Rp ')
                ->disabled()
                ->dehydrated()
                ->live()
                ->afterStateUpdated(fn ($state, $get, $set) => 
                    $set('subtotal', ($get('quantity') ?? 0) * ($get('price_at_sale') ?? 0))
                ),
            ]);
    }
}
