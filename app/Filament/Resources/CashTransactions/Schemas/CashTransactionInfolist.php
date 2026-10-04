<?php

namespace App\Filament\Resources\CashTransactions\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_at')
                    ->label('Tanggal & Jam')
                    ->dateTime('l, d F Y H:i')
                    ->color('primary')
                    ->size('xl')
                    ->weight('bold'),

                TextEntry::make('type')
                    ->label('Jenis Transaksi')
                    ->badge()
                    ->color(fn ($state) => match(strtoupper($state)) {
                        'DEPOSIT' => 'success',
                        'EXPENSE' => 'danger',
                        default   => 'gray',
                    })
                    ->formatStateUsing(fn ($state, $record) => match(strtoupper($state)) {
                        'DEPOSIT' => str_contains(strtolower($record->description ?? ''), 'modal') ? 'Modal Masuk' : 'Setor Kas',
                        'EXPENSE' => 'Pengeluaran',
                        default   => $state,
                    })
                    ->size('lg'),

                TextEntry::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->color(fn ($state, $record) => ($record->type === 'DEPOSIT' && str_contains(strtolower($record->description ?? ''), 'modal')) ? 'success' : 'danger')
                    ->size('xxl')
                    ->weight('extrabold'),

                TextEntry::make('recordedBy.name')
                    ->label('Dicatat Oleh')
                    ->icon('heroicon-o-user')
                    ->placeholder('-'),

                TextEntry::make('onBehalfOf.name')
                    ->label('Atas Nama Kasir')
                    ->icon('heroicon-o-identification')
                    ->placeholder('-'),

                TextEntry::make('description')
                    ->label('Keterangan')
                    ->columnSpanFull()
                    ->markdown()
                    ->placeholder('-'),

                ImageEntry::make('proof_image')
                    ->label('Foto Bukti Setoran / Nota Fisik')
                    ->disk('public')
                    ->columnSpanFull()
                    ->placeholder('Tidak ada foto bukti dilampirkan'),
            ]);
    }
}
