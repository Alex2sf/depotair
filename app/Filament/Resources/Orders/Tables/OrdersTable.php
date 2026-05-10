<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;

use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->searchable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Count::make()->label('Total Order')),
                TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('staff.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('courier.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status'),
                TextColumn::make('order_type'),
                TextColumn::make('payment_type'),
                TextColumn::make('delivery_scheduled_at')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-')
                    ->sortable(),
                TextColumn::make('latitude')
                    ->sortable(),
                TextColumn::make('longitude')
                    ->sortable(),
                TextColumn::make('subtotal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('delivery_fee')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('additional_fee')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                TextColumn::make('delivery_time')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-')
                    ->sortable(),
                TextColumn::make('completed_time')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-')
                    ->sortable(),
                TextColumn::make('durasi_siap')
                    ->label('Durasi Sejak Siap')
                    ->state(function ($record) {
                        if (!$record->ready_time) return '-';
                        $end = $record->completed_time ?? now();
                        // Pastikan tipe data datetime/carbon
                        $start = $record->ready_time;
                        
                        $diff = $start->diff($end);
                        
                        $parts = [];
                        if ($diff->d > 0) $parts[] = $diff->d . ' hari';
                        if ($diff->h > 0) $parts[] = $diff->h . ' jam';
                        if ($diff->i > 0) $parts[] = $diff->i . ' menit';
                        
                        return empty($parts) ? '< 1 menit' : implode(' ', $parts);
                    }),

                TextColumn::make('created_at')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : '-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // FILTER BULAN INI
                Filter::make('bulan_ini')
                    ->label('Bulan Ini')
                    ->query(fn (Builder $query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                    ->toggle(),

                // FILTER TANGGAL ORDER (KEREN BANGET!)
                \App\Filament\Filters\PeriodeFilter::make('created_at', 'created_at', 'Tanggal Order'),
                
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->label('Status Pesanan')
                    ->options(\App\Enums\OrderStatus::class)
                    ->multiple(),

                \Filament\Tables\Filters\SelectFilter::make('order_type')
                    ->label('Tipe Order')
                    ->options(\App\Enums\OrderType::class),

                \Filament\Tables\Filters\SelectFilter::make('payment_type')
                    ->label('Metode Pembayaran')
                    ->options(\App\Enums\PaymentType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->headerActions([
                \pxlrbt\FilamentExcel\Actions\Tables\ExportAction::make()->exports([
                    \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Orders-' . date('Y-m-d')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    \pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction::make()->exports([
                        \pxlrbt\FilamentExcel\Exports\ExcelExport::make()->fromTable()->withFilename('Orders-' . date('Y-m-d')),
                    ]),
                ]),
            ]);
    }
}
