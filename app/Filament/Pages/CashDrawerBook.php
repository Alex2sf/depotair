<?php

namespace App\Filament\Pages;

use App\Models\CashBalance;
use App\Models\CashDrawerLedger;
use Filament\Pages\Page;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashDrawerBook extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';
    protected string $view = 'filament.pages.general-journal';
    protected static ?string $navigationLabel = 'Buku Kas Laci';
    protected static string | \UnitEnum | null $navigationGroup = 'Keuangan';
    protected static ?string $title = 'Buku Kas Laci';
    protected static ?string $slug = 'buku-kas-laci';

    private static function rupiah(int $value): string
    {
        return ($value < 0 ? '-' : '') . 'Rp ' . number_format(abs($value), 0, ',', '.');
    }

    /** Saldo buku (akhir riwayat) dibandingkan saldo laci di cash_balances. */
    public static function reconciliation(): array
    {
        $book = (int) (CashDrawerLedger::query()->orderByDesc('transaction_date')->orderByDesc('id')->value('running_balance') ?? 0);
        $system = (int) (CashBalance::where('type', CashBalance::CASHIER)->value('balance') ?? 0);

        return ['book' => $book, 'system' => $system, 'difference' => $system - $book];
    }

    public function table(Table $table): Table
    {
        $r = self::reconciliation();
        $status = $r['difference'] === 0
            ? 'Cocok dengan saldo laci di sistem.'
            : 'Beda ' . self::rupiah($r['difference']) . ' dengan saldo laci di sistem (' . self::rupiah($r['system']) . '). Ada perubahan saldo tanpa transaksi, misalnya tutup shift versi lama.';

        return $table
            ->heading('Saldo buku: ' . self::rupiah($r['book']))
            ->description($status)
            ->query(CashDrawerLedger::query()->with('recordedBy'))
            ->defaultSort(fn ($query) => $query->orderByDesc('transaction_date')->orderByDesc('id'))
            ->columns([
                TextColumn::make('transaction_date')
                    ->label('Tanggal & Jam')
                    ->dateTime('d/m/Y H:i')
                    ->weight('bold'),

                TextColumn::make('category')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Penjualan', 'Modal', 'Uang Masuk Lain' => 'success',
                        'Setor Kas Besar' => 'info',
                        'Batal' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->searchable()
                    ->limit(45)
                    ->tooltip(fn ($record) => $record->description),

                TextColumn::make('recordedBy.name')
                    ->label('Oleh')
                    ->placeholder('-'),

                TextColumn::make('amount_in')
                    ->label('Masuk')
                    ->formatStateUsing(fn ($state) => $state > 0 ? self::rupiah((int) $state) : '-')
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->summarize(Sum::make()->label('Total Masuk')->formatStateUsing(fn ($state) => self::rupiah((int) $state))),

                TextColumn::make('amount_out')
                    ->label('Keluar')
                    ->formatStateUsing(fn ($state) => $state > 0 ? self::rupiah((int) $state) : '-')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'gray')
                    ->summarize(Sum::make()->label('Total Keluar')->formatStateUsing(fn ($state) => self::rupiah((int) $state))),

                TextColumn::make('running_balance')
                    ->label('Saldo Laci')
                    ->formatStateUsing(fn ($state) => self::rupiah((int) $state))
                    ->weight('bold'),
            ])
            ->filters([
                \App\Filament\Filters\PeriodeFilter::make('transaction_date', 'transaction_date', 'Periode'),

                SelectFilter::make('category')
                    ->label('Jenis')
                    ->options([
                        'Penjualan' => 'Penjualan',
                        'Modal' => 'Modal',
                        'Belanja Stok' => 'Belanja Stok',
                        'Operasional' => 'Operasional',
                        'Batal' => 'Batal',
                        'Setor Kas Besar' => 'Setor Kas Besar',
                        'Uang Masuk Lain' => 'Uang Masuk Lain',
                        'Uang Keluar Lain' => 'Uang Keluar Lain',
                    ]),
            ]);
    }
}
