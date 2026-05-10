<?php

namespace App\Filament\Resources\CashTransactions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CashTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold')
                    ->summarize(\Filament\Tables\Columns\Summarizers\Count::make()->label('Total Transaksi')),

                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'DEPOSIT' => 'success',
                        'EXPENSE' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('amount')
                    ->label('Jumlah')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color(fn ($record) => $record->type === 'DEPOSIT' ? 'success' : 'danger')
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50),

                TextColumn::make('recordedBy.name')
                    ->label('Dicatat Oleh')
                    ->sortable(),

                TextColumn::make('onBehalfOf.name')
                    ->label('Atas Nama')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Transaksi')
                    ->options([
                        'EXPENSE' => 'Pengeluaran',
                        'DEPOSIT' => 'Pemasukan',
                    ]),
                
                \App\Filament\Filters\PeriodeFilter::make('created_at', 'created_at', 'Periode Transaksi'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('CashTransactions-' . date('Y-m-d')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('CashTransactions-' . date('Y-m-d')),
                    ]),
                ]),
            ]);
    }
}
