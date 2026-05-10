<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\ProductType;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;

use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;

use App\Models\Product;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nama Produk')
                ->required()
                ->maxLength(255),

            TextInput::make('sku')
                ->label('SKU / Kode')
                ->required()
                ->unique(ignoreRecord: true),

            Radio::make('image_source_type')
                ->label('Tipe Gambar')
                ->options([
                    'upload' => 'Upload Gambar PC',
                    'url' => 'URL Gambar Internet',
                ])
                ->default('upload')
                ->inline()
                ->live()
                ->columnSpanFull(),

            FileUpload::make('image_upload')
                ->label('Upload Foto Produk')
                ->image()
                ->disk('public')
                ->directory('products')
                ->visible(fn ($get) => $get('image_source_type') === 'upload')
                ->required(fn ($get) => $get('image_source_type') === 'upload')
                ->columnSpanFull(),

            TextInput::make('image_url_link')
                ->label('URL Foto Produk')
                ->url()
                ->placeholder('https://example.com/image.jpg')
                ->visible(fn ($get) => $get('image_source_type') === 'url')
                ->required(fn ($get) => $get('image_source_type') === 'url')
                ->columnSpanFull(),

            Select::make('product_type')
                ->label('Tipe Produk')
                ->options(ProductType::class)
                ->required(),

            TextInput::make('unit')
                ->label('Satuan')
                ->required()
                ->placeholder('pcs, galon, liter, dll'),

            TextInput::make('price')
                ->label('Harga Jual')
                ->required()
                ->numeric()
                ->prefix('Rp ')
                ->placeholder('25000'),

            TextInput::make('cogs')
                ->label('Harga Pokok (COGS)')
                ->required()
                ->numeric()
                ->prefix('Rp ')
                ->placeholder('15000'),

            Textarea::make('description')
                ->label('Deskripsi')
                ->rows(3)
                ->columnSpanFull(),

            Toggle::make('is_enabled')
                ->label('Aktif / Dijual')
                ->required()
                ->default(true),
        ]);
    }
}