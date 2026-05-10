<?php

namespace App\Filament\Resources\CashBalances\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashBalanceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
            TextEntry::make('type')
                ->label('Jenis Saldo')
                ->badge()
                ->color(fn ($state) => $state === 'CASHIER' ? 'warning' : 'success')
                ->formatStateUsing(fn ($state) => $state === 'CASHIER' ? 'Kasir (Laci)' : 'Owner / Utama')
                ->size('xl')
                ->weight('bold'),

            TextEntry::make('balance')
                ->label('Saldo Saat Ini')
                ->money('IDR')
                ->color('primary')
                ->size('xxl')
                ->weight('extrabold'),

            TextEntry::make('last_transaction_at')
                ->label('Transaksi Terakhir')
                ->dateTime('d F Y H:i')
                ->placeholder('Belum ada transaksi')
                ->color('gray'),

            TextEntry::make('transactions_count')
                ->label('Total Transaksi')
                ->getStateUsing(fn ($record) => $record->transactions()->count())
                ->badge()
                ->color('info'),

            TextEntry::make('notes')
                ->label('Catatan')
                ->columnSpanFull()
                ->placeholder('Tidak ada catatan')
                ->markdown(),
            ]);
    }
}
