<?php

namespace App\Filament\Resources\OrderProducts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
TextColumn::make('order.order_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->order_id 
                        ? route('filament.admin.resources.orders.view', $record->order_id)
                        : null
                    )
                    ->openUrlInNewTab()
                    ->color('primary')
                    ->weight('bold')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Count::make()->label('Total Item Terjual')),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->color('info'),

                TextColumn::make('price_at_sale')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->weight('bold'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                        ->withFilename('OrderProducts-' . date('Y-m-d'))
                        ->withColumns([
                            \pxlrbt\FilamentExcel\Columns\Column::make('order.order_number')->heading('Order'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('product.name')->heading('Produk'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('quantity')->heading('Qty'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('price_at_sale')->heading('Harga'),
                            \pxlrbt\FilamentExcel\Columns\Column::make('subtotal')->heading('Subtotal'),
                        ]),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()
                            ->withFilename('OrderProducts-' . date('Y-m-d'))
                            ->withColumns([
                                \pxlrbt\FilamentExcel\Columns\Column::make('order.order_number')->heading('Order'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('product.name')->heading('Produk'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('quantity')->heading('Qty'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('price_at_sale')->heading('Harga'),
                                \pxlrbt\FilamentExcel\Columns\Column::make('subtotal')->heading('Subtotal'),
                            ]),
                    ]),
                ]),
            ]);
    }
}
