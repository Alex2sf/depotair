<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name')
                ->label('Nama Lengkap')
                ->size('xl')
                ->weight('bold'),

            TextEntry::make('phone_number')
                ->label('Nomor HP')
                ->icon('heroicon-o-phone')
                ->copyable()
                ->copyMessage('Nomor HP disalin!'),

            TextEntry::make('address')
                ->label('Alamat')
                ->columnSpanFull(),

            TextEntry::make('latitude')
                ->label('Latitude')
                ->placeholder('-'),

            TextEntry::make('longitude')
                ->label('Longitude')
                ->placeholder('-'),

            TextEntry::make('created_at')
                ->label('Terdaftar Sejak')
                ->dateTime('d/m/Y H:i'),

            TextEntry::make('updated_at')
                ->label('Terakhir Diperbarui')
                ->dateTime('d/m/Y H:i'),
        ]);
    }
}