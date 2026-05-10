<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentType;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number'),
                Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} - {$record->phone_number}")
                    ->searchable(['name', 'phone_number'])
                    ->required(),
                Select::make('staff_id')
                    ->relationship('staff', 'name'),
                Select::make('courier_id')
                    ->relationship('courier', 'name'),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->default('DRAFT')
                    ->required(),
                Select::make('order_type')
                    ->options(OrderType::class)
                    ->default('SELF_PICKUP')
                    ->required(),
                Select::make('payment_type')
                    ->options(PaymentType::class)
                    ->required(),
                Textarea::make('delivery_address')
                    ->columnSpanFull(),
                Textarea::make('address_link')
                    ->columnSpanFull(),
                DateTimePicker::make('delivery_scheduled_at'),
                TextInput::make('latitude')
                    ->numeric(),
                TextInput::make('longitude')
                    ->numeric(),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('delivery_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('additional_fee')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total_amount')
                    ->required()
                    ->readOnly()
                    ->numeric()
                    ->default(0)
                    ->prefix('Rp'),

                \Filament\Forms\Components\Repeater::make('orderProducts')
                    ->relationship()
                    ->schema([
                        Select::make('product_id')
                            ->label('Produk')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set) {
                                $product = \App\Models\Product::find($state);
                                if ($product) {
                                    $set('product_name', $product->name);
                                    $set('price_at_sale', $product->price);
                                    $set('cogs_at_sale', $product->cogs);
                                    $set('quantity', 1);
                                }
                            })
                            ->columnSpan(4),
                        
                        \Filament\Forms\Components\Hidden::make('product_name'),
                        \Filament\Forms\Components\Hidden::make('cogs_at_sale'),

                        TextInput::make('quantity')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->required()
                            ->reactive()
                            ->columnSpan(2),

                        TextInput::make('price_at_sale')
                            ->label('Harga Satuan')
                            ->numeric()
                            ->readOnly()
                            ->prefix('Rp')
                            ->dehydrated()
                            ->columnSpan(3),

                        TextInput::make('subtotal')
                            ->label('Subtotal')
                            ->numeric()
                            ->placeholder('Auto')
                            ->readOnly() // Calculated by model event
                            ->columnSpan(3),
                    ])
                    ->columns(12)
                    ->columnSpanFull()
                    ->live()
                    ->afterStateUpdated(function (\Filament\Forms\Get $get, \Filament\Forms\Set $set) {
                        // Simple client-side recalc for total amount display if needed, 
                        // but Model handles the real logic. 
                        // For now, let's just make it clear.
                        $items = $get('orderProducts') ?? [];
                        $subtotal = 0;
                        foreach ($items as $item) {
                            $qty = intval($item['quantity'] ?? 0);
                            $price = intval($item['price_at_sale'] ?? 0);
                            $subtotal += $qty * $price;
                        }
                        $set('subtotal', $subtotal);
                        $set('total_amount', $subtotal + (int)$get('delivery_fee') + (int)$get('additional_fee'));
                    }),
                Textarea::make('notes')
                    ->columnSpanFull(),
                DateTimePicker::make('delivery_time'),
                DateTimePicker::make('completed_time'),
            ]);
    }
}
