<?php

namespace App\Filament\Resources\CashierPurchases\Tables;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CashierPurchasesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal & Jam')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'STOCK' => 'info',
                        'OPERATIONAL' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'STOCK' => 'Stok Barang',
                        'OPERATIONAL' => 'Operasional Toko',
                        default => $state,
                    }),

                TextColumn::make('product.name')
                    ->label('Barang (Qty)')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->product) {
                            return "{$record->product->name} (" . number_format($record->quantity) . " pcs)";
                        }
                        return '-';
                    })
                    ->placeholder('-'),

                TextColumn::make('amount')
                    ->label('Nominal Belanja')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->color('danger')
                    ->weight('bold')
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->label('Total')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(35)
                    ->tooltip(fn ($record) => $record->description),

                ImageColumn::make('proof_image')
                    ->label('Foto Nota')
                    ->disk('public')
                    ->square()
                    ->size(45)
                    ->url(fn ($record) => $record->proof_image_url)
                    ->openUrlInNewTab()
                    ->placeholder('Tidak ada foto'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'STOCK' => 'Stok Barang',
                        'OPERATIONAL' => 'Operasional Toko',
                    ]),

                SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                \App\Filament\Filters\PeriodeFilter::make('created_at', 'created_at', 'Periode Belanja'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('preview_nota')
                    ->label('Foto Nota')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->proof_image))
                    ->modalHeading('Bukti Foto Nota / Struk Belanja')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div class="flex justify-center p-2">
                            <img src="' . e($record->proof_image_url) . '" alt="Bukti Nota" class="max-h-[70vh] rounded-lg shadow-lg object-contain" />
                        </div>'
                    )),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Belanja-Kasir-' . date('Y-m-d')),
                ]),
            ]);
    }
}
