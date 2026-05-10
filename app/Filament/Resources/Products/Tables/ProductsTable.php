<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Count::make()->label('Total')),

                TextColumn::make('sku')
                    ->searchable()
                    ->label('Kode'),

                TextColumn::make('product_type')
                    ->badge()
                    ->color(fn ($state) => $state->getColor()),

                TextColumn::make('unit')
                    ->label('Satuan'),

                TextColumn::make('price')
                    ->label('Harga')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable()
                    ->color('success'),

                TextColumn::make('cogs')
                    ->label('HPP')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('is_enabled')
                    ->label('Aktif')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state ? 'Ya' : 'Tidak'),

                ImageColumn::make('image_url')
                    ->label('Foto')
                    ->disk('public')
                    ->getStateUsing(fn ($record) => $record->getRawOriginal('image_url'))
                    ->height(50)
                    ->width(50)
                    ->rounded(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('product_type')
                    ->label('Tipe Produk')
                    ->options(\App\Enums\ProductType::class),
                \Filament\Tables\Filters\TernaryFilter::make('is_enabled')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif Saja')
                    ->falseLabel('Tidak Aktif'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Products-' . date('Y-m-d')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Products-' . date('Y-m-d')),
                    ]),
                ]),
            ]);
    }
}
