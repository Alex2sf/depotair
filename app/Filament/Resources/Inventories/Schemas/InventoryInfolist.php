<?php

namespace App\Filament\Resources\Inventories\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Schemas\Schema;

class InventoryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('product.name')
                ->label('Produk')
                ->size('xl')
                ->weight('bold'),

            TextEntry::make('quantity')
                ->label('Stok Sistem')
                ->numeric()
                ->color('primary'),

            TextEntry::make('real_quantity')
                ->label('Stok Fisik (Opname)')
                ->numeric()
                ->color(fn ($state, $record) => 
                    $state !== null && $state != $record->quantity ? 'danger' : 'success'
                )
                ->icon(fn ($state, $record) => 
                    $state !== null && $state != $record->quantity ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'
                ),

            TextEntry::make('selisih')
                ->label('Selisih Stok')
                ->getStateUsing(fn ($record) => 
                    $record->real_quantity !== null 
                        ? $record->real_quantity - $record->quantity 
                        : 'Belum opname'
                )
                ->badge()
                ->color(fn ($state) => 
                    $state === 'Belum opname' ? 'gray' :
                    ($state == 0 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                ),

            TextEntry::make('last_opname_at')
                ->label('Terakhir Diopname')
                ->date('d/m/Y')
                ->placeholder('Belum pernah opname'),

            TextEntry::make('low_stock_threshold')
                ->label('Batas Stok Rendah')
                ->numeric(),

            IconEntry::make('is_low_stock')
                ->label('Status Stok')
                ->boolean()
                ->trueIcon('heroicon-o-exclamation-triangle')
                ->trueColor('danger')
                ->falseColor('success')
                ->getStateUsing(fn ($record) => $record->quantity <= $record->low_stock_threshold),
        ]);
    }
}