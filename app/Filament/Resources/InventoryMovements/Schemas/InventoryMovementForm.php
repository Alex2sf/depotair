<?php

namespace App\Filament\Resources\InventoryMovements\Schemas;

use App\Enums\MovementReason;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Hidden;

class InventoryMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->afterStateUpdated(fn ($state, $set) => $set('quantity_before',
                    \App\Models\Inventory::where('product_id', $state)->first()?->quantity ?? 0
                )),

            TextInput::make('quantity_before')
                ->disabled()
                ->dehydrated() // tetap disimpan ke DB
                ->numeric()
                ->label('Stok Saat Ini'),

            TextInput::make('quantity_change')
                ->required()
                ->numeric()
                ->reactive()
                ->afterStateUpdated(function ($state, $get, $set) {
                    $before = $get('quantity_before') ?? 0;
                    $set('quantity_after', $before + $state);
                })
                ->label('Tambah/Kurang'),

            TextInput::make('quantity_after')
                ->disabled()
                ->dehydrated()
                ->numeric()
                ->label('Stok Setelahnya'),

            Select::make('reason')
                ->options(\App\Enums\MovementReason::class)
                ->required(),

            Textarea::make('notes')->columnSpanFull(),

            Hidden::make('user_id')->default(auth()->id()),
        ]);
    }
}
