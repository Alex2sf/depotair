<?php

namespace App\Filament\Resources\OrderProducts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderProductInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
TextEntry::make('order.order_number')
                ->label('Order')
                ->url(fn ($record) => route('filament.admin.resources.orders.view', $record->order))
                ->color('primary')
                ->weight('bold'),

            TextEntry::make('product.name')
                ->label('Produk')
                ->size('xl')
                ->weight('bold'),

            TextEntry::make('quantity')
                ->label('Jumlah')
                ->numeric()
                ->color('info'),

            TextEntry::make('price_at_sale')
                ->label('Harga Jual')
                ->money('IDR')
                ->color('success'),

            TextEntry::make('cogs_at_sale')
                ->label('HPP')
                ->money('IDR')
                ->color('warning'),

            TextEntry::make('subtotal')
                ->label('Subtotal')
                ->money('IDR')
                ->color('success')
                ->size('lg')
                ->weight('bold'),
            ]);
    }
}
