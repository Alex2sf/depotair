<?php

namespace App\Filament\Resources\CashierShifts\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashierShiftInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Shift')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('user.name')
                                ->label('Kasir Bertugas')
                                ->weight('bold')
                                ->icon('heroicon-o-user'),

                            TextEntry::make('status')
                                ->label('Status Shift')
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

                            TextEntry::make('start_time')
                                ->label('Waktu Buka Shift')
                                ->dateTime('l, d F Y - H:i')
                                ->icon('heroicon-o-arrow-right-on-rectangle'),

                            TextEntry::make('end_time')
                                ->label('Waktu Tutup Shift')
                                ->dateTime('l, d F Y - H:i')
                                ->placeholder('Shift Masih Dibuka')
                                ->icon('heroicon-o-arrow-left-on-rectangle'),
                        ]),
                    ]),

                Section::make('Rekonsiliasi Kas Laci Kasir')
                    ->schema([
                        Grid::make(4)->schema([
                            TextEntry::make('starting_cash')
                                ->label('Uang di Laci (Awal)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('lg')
                                ->color('info'),

                            TextEntry::make('cash_sales')
                                ->label('Penjualan Tunai (+)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('lg')
                                ->color('success'),

                            TextEntry::make('cash_expenses')
                                ->label('Belanja Kasir (-)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('lg')
                                ->color('danger'),

                            TextEntry::make('cash_deposited')
                                ->label('Setor Kas Besar (-)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                                ->size('lg')
                                ->color('warning'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('expected_cash')
                                ->label('Uang Seharusnya (Sistem)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('xl')
                                ->weight('bold'),

                            TextEntry::make('actual_cash')
                                ->label('Uang Fisik Aktual (Kasir)')
                                ->formatStateUsing(fn ($state) => $state !== null ? 'Rp ' . number_format($state, 0, ',', '.') : 'Belum Input')
                                ->size('xl')
                                ->weight('bold')
                                ->color('primary'),

                            TextEntry::make('difference')
                                ->label('Status Selisih')
                                ->badge()
                                ->size('lg')
                                ->color(fn ($state) => match (true) {
                                    $state == 0 => 'success',
                                    $state < 0 => 'danger',
                                    default => 'warning',
                                })
                                ->formatStateUsing(fn ($state, $record) => match (true) {
                                    $record->status === 'OPEN' => 'Shift Masih Aktif',
                                    $state == 0 => 'Pas / Klop (Rp 0)',
                                    $state < 0 => 'Tekor -Rp ' . number_format(abs($state), 0, ',', '.'),
                                    default => 'Lebih +Rp ' . number_format($state, 0, ',', '.'),
                                }),
                        ]),
                    ]),

                Section::make('Catatan Kasir')
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan saat Tutup Shift')
                            ->placeholder('Tidak ada catatan dari kasir.')
                            ->markdown(),
                    ]),
            ]);
    }
}
