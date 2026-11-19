<?php

namespace App\Filament\Pages;

use App\Models\GeneralJournal as GeneralJournalModel;
use App\Support\BcaStatementExport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
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

    public const ACCOUNT_LABELS = [
        'LACI' => 'Laci Kasir',
        'KAS_BESAR' => 'Kas Besar',
        'QRIS' => 'QRIS',
        'TRANSFER' => 'Transfer',
        'CORPORATE' => 'Corporate',
    ];

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
                        'Setor Kas' => 'danger',
                        'Modal Kas' => 'success',
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

                TextColumn::make('account')
                    ->label('Akun')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::ACCOUNT_LABELS[$state] ?? (string) $state)
                    ->color(fn (?string $state): string => match ($state) {
                        'LACI' => 'warning',
                        'KAS_BESAR' => 'danger',
                        'QRIS' => 'primary',
                        'TRANSFER' => 'info',
                        default => 'gray',
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

                SelectFilter::make('account')
                    ->label('Akun / Rekening')
                    ->options(self::ACCOUNT_LABELS),

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
                        'Setor Kas' => 'Setor Kas (Uang Keluar)',
                        'Modal Kas' => 'Modal Kas (Uang Masuk)',
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
                Action::make('export_bca')
                    ->label('Export Format BCA')
                    ->icon('heroicon-o-building-library')
                    ->color('primary')
                    ->modalHeading('Export Mutasi Format BCA')
                    ->modalDescription('Kolom sama dengan CSV mutasi BCA (Tanggal; Jam; Keterangan; Jumlah; CR/DB; Saldo) agar bisa dibandingkan berdampingan.')
                    ->modalSubmitActionLabel('Download CSV')
                    ->schema([
                        Select::make('account')
                            ->label('Akun')
                            ->options(BcaStatementExport::ACCOUNTS)
                            ->default('QRIS')
                            ->required(),
                        DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->default(now()->startOfMonth())
                            ->required(),
                        DatePicker::make('to')
                            ->label('Sampai Tanggal')
                            ->default(now())
                            ->afterOrEqual('from')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $from = Carbon::parse($data['from']);
                        $to = Carbon::parse($data['to']);
                        $csv = BcaStatementExport::toCsv(BcaStatementExport::rows($data['account'], $from, $to));
                        $name = 'mutasi-' . strtolower($data['account']) . '-' . $from->format('Ymd') . '-' . $to->format('Ymd') . '.csv';

                        return response()->streamDownload(fn () => print($csv), $name, ['Content-Type' => 'text/csv']);
                    }),

                Action::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $query = $this->getFilteredSortedTableQuery()?->get()
                            ?? GeneralJournalModel::query()->orderByDesc('transaction_date')->get();

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
