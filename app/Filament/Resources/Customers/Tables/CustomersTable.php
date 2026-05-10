<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
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

                TextColumn::make('phone_number')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-o-phone')
                    ->label('HP'),

                TextColumn::make('address')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->address)
                    ->searchable(),

                TextColumn::make('latitude')
                    ->label('Lat')
                    ->placeholder('-'),

                TextColumn::make('longitude')
                    ->label('Lng')
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                \App\Filament\Filters\PeriodeFilter::make('created_at', 'created_at', 'Terdaftar Pada'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Customers-' . date('Y-m-d')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Customers-' . date('Y-m-d')),
                    ]),
                ]),
            ]);
    }
}
