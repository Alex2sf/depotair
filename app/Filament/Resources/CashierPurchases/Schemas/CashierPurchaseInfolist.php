<?php

namespace App\Filament\Resources\CashierPurchases\Schemas;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashierPurchaseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Rincian Belanja Kasir')
                    ->schema([
                        Grid::make(3)->schema([
                            TextEntry::make('created_at')
                                ->label('Tanggal & Waktu Belanja')
                                ->dateTime('l, d F Y - H:i')
                                ->icon('heroicon-o-calendar'),

                            TextEntry::make('user.name')
                                ->label('Kasir yang Mencatat')
                                ->weight('bold')
                                ->icon('heroicon-o-user'),

                            TextEntry::make('amount')
                                ->label('Nominal Pengeluaran')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('xl')
                                ->weight('extrabold')
                                ->color('danger'),
                        ]),

                        Grid::make(3)->schema([
                            TextEntry::make('category')
                                ->label('Kategori Pengeluaran')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'STOCK' => 'info',
                                    'OPERATIONAL' => 'warning',
                                    default => 'gray',
                                })
                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                    'STOCK' => 'Stok Barang Toko',
                                    'OPERATIONAL' => 'Operasional Toko',
                                    default => $state,
                                }),

                            TextEntry::make('product.name')
                                ->label('Item Barang')
                                ->placeholder('-'),

                            TextEntry::make('quantity')
                                ->label('Jumlah (Qty)')
                                ->formatStateUsing(fn ($state) => number_format($state) . ' pcs')
                                ->placeholder('-'),
                        ]),

                        TextEntry::make('description')
                            ->label('Keterangan / Keperluan')
                            ->columnSpanFull()
                            ->markdown()
                            ->placeholder('Tidak ada keterangan tambahan.'),
                    ]),

                Section::make('Foto Bukti Struk / Nota')
                    ->schema([
                        ImageEntry::make('proof_image')
                            ->label('Bukti Fisik Pembelian')
                            ->disk('public')
                            ->size(300)
                            ->columnSpanFull()
                            ->placeholder('Tidak ada foto bukti diunggah.'),
                    ]),
            ]);
    }
}
