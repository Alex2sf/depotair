<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Lengkap')
                ->required()
                ->maxLength(255),

            TextInput::make('phone_number')
                ->label('Nomor HP')
                ->tel()
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(20)
                ->placeholder('081234567890'),

            Textarea::make('address')
                ->label('Alamat Lengkap')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            TextInput::make('latitude')
                ->label('Latitude')
                ->numeric()
                ->step('any')
                ->placeholder('-6.123456'),

            TextInput::make('longitude')
                ->label('Longitude')
                ->numeric()
                ->step('any')
                ->placeholder('106.123456'),
        ]);
    }
}