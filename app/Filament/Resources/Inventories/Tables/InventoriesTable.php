<?php

namespace App\Filament\Resources\Inventories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
 ->columns([
                TextColumn::make('product.name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->label('Produk')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Count::make()->label('Total Item')),

                TextColumn::make('quantity')
                    ->label('Stok Sistem')
                    ->sortable()
                    ->color('primary'),

                TextColumn::make('real_quantity')
                    ->label('Stok Fisik')
                    ->badge()
                    ->color(fn ($state, $record) => 
                        $state === null ? 'gray' :
                        ($state == $record->quantity ? 'success' : 'danger')
                    )
                    ->icon(fn ($state, $record) => 
                        $state === null ? 'heroicon-o-question-mark-circle' :
                        ($state == $record->quantity ? 'heroicon-o-check-circle' : 'heroicon-o-exclamation-triangle')
                    )
                    ->formatStateUsing(fn ($state) => $state ?? 'Belum opname'),

                TextColumn::make('selisih')
                    ->label('Selisih')
                    ->getStateUsing(fn ($record) => 
                        $record->real_quantity !== null 
                            ? $record->real_quantity - $record->quantity 
                            : null
                    )
                    ->badge()
                    ->color(fn ($state) => 
                        is_null($state) ? 'gray' :
                        ($state == 0 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    )
                    ->icon(fn ($state) => 
                        is_null($state) ? 'heroicon-o-minus' :
                        ($state == 0 ? 'heroicon-o-check' : ($state > 0 ? 'heroicon-o-arrow-up' : 'heroicon-o-arrow-down'))
                    ),

                TextColumn::make('low_stock_threshold')
                    ->sortable(),

                TextColumn::make('last_opname_at')
                    ->label('Terakhir Opname')
                    ->date('d/m/Y')
                    ->placeholder('Belum pernah')
                    ->sortable(),

    
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('product_id')
                    ->label('Produk')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
                
                \Filament\Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Menipis')
                    ->query(fn (\Illuminate\Database\Eloquent\Builder $query) => 
                        $query->whereRaw('quantity <= low_stock_threshold')
                    )
                    ->toggle(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Inventories-' . date('Y-m-d')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Inventories-' . date('Y-m-d')),
                    ]),
                ]),
            ]);
    }
}
