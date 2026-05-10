<?php

namespace App\Filament\Resources\CashBalances\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashBalancesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn ($state) => $state === 'CASHIER' ? 'warning' : 'success')
                    ->formatStateUsing(fn ($state) => $state === 'CASHIER' ? 'Kasir' : 'Owner')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Count::make()->label('Total Saldo')),

                TextColumn::make('balance')
                    ->label('Saldo')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('last_transaction_at')
                    ->label('Transaksi Terakhir')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Belum ada')
                    ->sortable(),

                TextColumn::make('transactions_count')
                    ->label('Jumlah Transaksi')
                    ->getStateUsing(fn ($record) => $record->transactions()->count())
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Saldo')
                    ->options([
                        'CASHIER' => 'Kasir',
                        'MAIN'    => 'Owner',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('CashBalances-' . date('Y-m-d')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('CashBalances-' . date('Y-m-d')),
                    ]),
                ]),
            ]);
    }
}
