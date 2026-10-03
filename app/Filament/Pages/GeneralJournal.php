<?php

namespace App\Filament\Pages;

use App\Models\GeneralJournal as GeneralJournalModel;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class GeneralJournal extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';
    protected string $view = 'filament.pages.general-journal';
    protected static ?string $navigationLabel = 'Jurnal Umum';
    protected static string | \UnitEnum | null $navigationGroup = 'Keuangan';
    protected static ?string $title = 'Jurnal Umum (Buku Kas)';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Pages\GeneralJournal\Widgets\GeneralJournalStatsOverview::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return 1;
    }

    public function getWidgetData(): array
    {
        return [
            'tableFilters' => $this->tableFilters,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Linimasa Arus Kas Jurnal Umum')
            ->query(
                GeneralJournalModel::query()
                    ->orderByDesc('transaction_date')
            )
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal & Jam')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('ref_number')
                    ->label('No. Ref / Bukti')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor referensi disalin')
                    ->color('gray'),

                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Penjualan' => 'success',
                        'Belanja Stok' => 'info',
                        'Operasional Toko' => 'warning',
                        'Kas Masuk' => 'success',
                        'Pengeluaran Kas' => 'danger',
                        default => 'secondary',
                    })
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('payment_method')
                    ->label('Metode / Akun')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'TUNAI' => 'warning',
                        'QRIS' => 'primary',
                        'TRANSFER' => 'info',
                        'CORPORATE' => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('amount_in')
                    ->label('Masuk (Debit)')
                    ->formatStateUsing(fn ($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total Masuk')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),

                TextColumn::make('amount_out')
                    ->label('Keluar (Kredit)')
                    ->formatStateUsing(fn ($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->weight(fn ($state) => $state > 0 ? 'bold' : 'normal')
                    ->sortable()
                    ->summarize(Sum::make()->label('Total Keluar')->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))),

                ImageColumn::make('proof_image')
                    ->label('Bukti')
                    ->disk('public')
                    ->square()
                    ->size(40)
                    ->url(fn ($record) => $record->proof_image_url)
                    ->openUrlInNewTab()
                    ->placeholder('-'),
            ])
            ->filters([
                \App\Filament\Filters\PeriodeFilter::make('transaction_date', 'transaction_date', 'Periode Transaksi'),

                SelectFilter::make('type')
                    ->label('Jenis Mutasi')
                    ->options([
                        'PEMASUKAN' => 'Uang Masuk (+)',
                        'PENGELUARAN' => 'Uang Keluar (-)',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'TUNAI' => 'Tunai (Kas Fisik)',
                        'QRIS' => 'QRIS (Digital)',
                        'TRANSFER' => 'Transfer Bank',
                        'CORPORATE' => 'Corporate / Tempo',
                    ]),

                SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'Penjualan' => 'Penjualan Air/Barang',
                        'Belanja Stok' => 'Belanja Stok',
                        'Operasional Toko' => 'Operasional Toko',
                        'Kas Masuk' => 'Kas Masuk (Deposit)',
                        'Pengeluaran Kas' => 'Pengeluaran Kas',
                    ]),
            ])
            ->recordActions([
                Action::make('preview_nota')
                    ->label('Foto')
                    ->icon('heroicon-o-photo')
                    ->color('info')
                    ->visible(fn ($record) => !empty($record->proof_image))
                    ->modalHeading('Foto Bukti Transaksi')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalContent(fn ($record) => new HtmlString(
                        '<div class="flex justify-center p-2">
                            <img src="' . e($record->proof_image_url) . '" alt="Bukti Transaksi" class="max-h-[70vh] rounded-lg shadow-lg object-contain" />
                        </div>'
                    )),
            ])
            ->headerActions([
                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $query = GeneralJournalModel::query()
                            ->orderByDesc('transaction_date')
                            ->get();

                        $csv = "Tanggal,No Referensi,Kategori,Keterangan,Metode,Masuk,Keluar\n";
                        foreach ($query as $row) {
                            $escapedDesc = str_replace('"', '""', $row->description);
                            $csv .= "{$row->transaction_date},{$row->ref_number},{$row->category},\"{$escapedDesc}\",{$row->payment_method},{$row->amount_in},{$row->amount_out}\n";
                        }

                        return response()->streamDownload(function () use ($csv) {
                            echo $csv;
                        }, 'jurnal-umum-' . now()->format('d-m-Y') . '.csv');
                    }),
            ]);
    }
}
