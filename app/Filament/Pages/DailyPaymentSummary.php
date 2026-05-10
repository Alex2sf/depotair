<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Actions\Action;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DailyPaymentSummary extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    protected string $view = 'filament.pages.daily-payment-summary';
    protected static ?string $navigationLabel = 'Laporan Pembayaran';
    protected static ?string $title = 'Rekap Pembayaran Harian';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                \App\Models\DailyPaymentReport::query()
                    ->orderByDesc('date')
            )
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('tunai_total')
                    ->label('Tunai')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),
                TextColumn::make('qris_total')
                    ->label('QRIS')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),
                TextColumn::make('transfer_total')
                    ->label('Transfer')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),
                TextColumn::make('corporate_total')
                    ->label('Corporate')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),
                TextColumn::make('grand_total')
                    ->label('Total Harian')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->weight('bold')
                    ->summarize(Sum::make()->label('Total')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),
            ])
                ->filters([
                 \App\Filament\Filters\PeriodeFilter::make('date', 'date', 'Periode Laporan') 
            ])
            ->headerActions([
               // CUSTOM CSV EXPORT ACTION
               Action::make('export_csv')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                     $query = \App\Models\DailyPaymentReport::query()
                        ->orderByDesc('date')
                        ->get();

                     $csv = "Tanggal,Tunai,QRIS,Transfer,Corporate,Total\n";
                     foreach($query as $row) {
                        $csv .= "{$row->date},{$row->tunai_total},{$row->qris_total},{$row->transfer_total},{$row->corporate_total},{$row->grand_total}\n";
                     }

                     return response()->streamDownload(function () use ($csv) {
                         echo $csv;
                     }, 'laporan-pembayaran-' . now()->format('d-m-Y') . '.csv');
                })
            ]);
    }
}
