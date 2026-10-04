<?php

namespace App\Filament\Resources\CashierShifts\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashierShiftsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'OPEN' => 'success',
                        'CLOSED' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'OPEN' => 'Sedang Aktif',
                        'CLOSED' => 'Sudah Tutup',
                        default => $state,
                    }),

                TextColumn::make('start_time')
                    ->label('Buka Shift')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('end_time')
                    ->label('Tutup Shift')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Masih Buka')
                    ->sortable(),

                TextColumn::make('starting_cash')
                    ->label('Uang di Laci')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('info')
                    ->sortable(),

                TextColumn::make('cash_sales')
                    ->label('Penjualan Tunai')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('success')
                    ->sortable(),

                TextColumn::make('cash_expenses')
                    ->label('Belanja Kasir')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('danger')
                    ->sortable(),

                TextColumn::make('cash_deposited')
                    ->label('Setor Kas')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('expected_cash')
                    ->label('Uang Seharusnya')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),

                TextColumn::make('actual_cash')
                    ->label('Uang Fisik Akhir')
                    ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->weight('bold')
                    ->placeholder('-'),

                TextColumn::make('difference')
                    ->label('Selisih')
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state == 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn ($state, $record) => match (true) {
                        $record->status === 'OPEN' => 'Shift Berjalan',
                        $state == 0 => 'Pas / Klop (Rp 0)',
                        $state < 0 => 'Tekor -Rp ' . number_format(abs($state), 0, ',', '.'),
                        default => 'Lebih +Rp ' . number_format($state, 0, ',', '.'),
                    }),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(25)
                    ->placeholder('-'),
            ])
            ->defaultSort('start_time', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Shift')
                    ->options([
                        'OPEN' => 'Sedang Aktif',
                        'CLOSED' => 'Sudah Tutup',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                \App\Filament\Filters\PeriodeFilter::make('start_time', 'start_time', 'Periode Buka Shift'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
