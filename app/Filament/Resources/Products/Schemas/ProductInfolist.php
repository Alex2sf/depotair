<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Schema;

class ProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')
                ->label('Nama Produk')
                ->size('xl')
                ->weight('bold'),

            TextEntry::make('sku')
                ->label('SKU')
                ->copyable(),

            ImageEntry::make('image_url')
                ->label('Foto Produk')
                ->disk('public')
                ->getStateUsing(fn ($record) => $record->getRawOriginal('image_url'))
                ->height(200)
                ->defaultImageUrl(asset('images/no-product.png')),

            TextEntry::make('product_type')
                ->label('Tipe')
                ->badge()
                ->color(fn ($state) => $state->getColor()),

            TextEntry::make('unit')
                ->label('Satuan'),

            TextEntry::make('price')
                ->label('Harga Jual')
                ->money('IDR')
                ->size('lg')
                ->color('success'),

            TextEntry::make('cogs')
                ->label('Harga Pokok')
                ->money('IDR')
                ->color('warning'),

            TextEntry::make('description')
                ->label('Deskripsi')
                ->columnSpanFull()
                ->placeholder('-'),

            IconEntry::make('is_enabled')
                ->label('Status')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->trueColor('success')
                ->falseColor('danger')
                ->falseIcon('heroicon-o-x-circle'),
        ]);
    }
}