<?php

namespace App\Filament\Resources\CashTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class CashTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
TextEntry::make('transaction_date')
                ->label('Tanggal & Jam')
                ->dateTime('l, d F Y H:i')
                ->color('primary')
                ->size('xl')
                ->weight('bold'),

            TextEntry::make('cashBalance.date')
                ->label('Shift Kas')
                ->date('d/m/Y')
                ->icon('heroicon-o-calendar'),

            TextEntry::make('cashBalance.user.name')
                ->label('Kasir Shift')
                ->placeholder('-'),

            // Di CashTransactionInfolist.php
            TextEntry::make('type')
                ->label('Jenis Transaksi')
                ->badge()
                ->color(fn ($state) => match(strtolower($state)) {
                    'sale'       => 'success',
                    'deposit'    => 'info',
                    'withdraw'   => 'warning',
                    'expense'    => 'danger',
                    'adjustment' => 'gray',
                    default      => 'gray',
                })
                ->formatStateUsing(fn ($state) => match(strtolower($state)) {
                    'sale'       => 'Penjualan Tunai',
                    'deposit'    => 'Setor Uang',
                    'withdraw'   => 'Tarik Uang',
                    'expense'    => 'Pengeluaran',
                    'adjustment'=> 'Penyesuaian',
                    default      => strtoupper($state),
                })
                ->size('lg'),

            TextEntry::make('amount')
                ->label('Jumlah')
                ->money('IDR')
                ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                ->size('xxl')
                ->weight('extrabold'),

            TextEntry::make('description')
                ->label('Keterangan')
                ->columnSpanFull()
                ->markdown()
                ->placeholder('-'),

            TextEntry::make('user.name')
                ->label('Dicatat Oleh')
                ->icon('heroicon-o-user'),

            TextEntry::make('created_at')
                ->label('Waktu Input')
                ->dateTime('d/m/Y H:i'),
            ]);
    }
}
