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

    /**
     * Samakan saldo buku dan saldo sistem dengan uang fisik hasil hitung.
     * Selisih buku dicatat sebagai satu transaksi penyesuaian (ada jejaknya), lalu cash_balances diset ke uang fisik.
     */
    public static function adjustToCounted(int $counted, int $userId, ?string $note = null): array
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($counted, $userId, $note) {
            $balance = CashBalance::where('type', CashBalance::CASHIER)->lockForUpdate()->firstOrFail();
            $book = self::reconciliation()['book'];
            $delta = $counted - $book;

            if ($delta !== 0) {
                \App\Models\CashTransaction::create([
                    'type'        => $delta > 0 ? \App\Models\CashTransaction::TYPE_DEPOSIT : \App\Models\CashTransaction::TYPE_EXPENSE,
                    'cash_type'   => 'CASHIER',
                    'amount'      => abs($delta),
                    'description' => 'Penyesuaian saldo laci ke uang fisik ' . self::rupiah($counted) . ($note ? ": {$note}" : ''),
                    'recorded_by' => $userId,
                ]);
            }

            $previousSystem = (int) $balance->balance;
            $balance->update(['balance' => $counted, 'last_transaction_at' => now()]);

            return ['book_before' => $book, 'system_before' => $previousSystem, 'counted' => $counted, 'adjustment' => $delta];
        });
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
            ->headerActions([
                \Filament\Actions\Action::make('adjust_to_counted')
                    ->label('Sesuaikan ke uang fisik')
                    ->icon('heroicon-o-scale')
                    ->color('warning')
                    ->visible(fn () => in_array(auth()->user()?->role, ['owner', 'admin', 'super_admin']))
                    ->modalHeading('Sesuaikan saldo laci ke uang fisik')
                    ->modalDescription('Hitung semua uang di laci saat tidak ada transaksi. Selisih dengan saldo buku dicatat sebagai satu transaksi penyesuaian, lalu saldo buku dan saldo sistem sama-sama menjadi uang fisik ini.')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('counted')
                            ->label('Uang fisik di laci (Rp)')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                        \Filament\Forms\Components\TextInput::make('note')
                            ->label('Catatan')
                            ->placeholder('Patok saldo awal / opname kas 22/11')
                            ->maxLength(200),
                    ])
                    ->requiresConfirmation()
                    ->action(function (array $data) {
                        $result = self::adjustToCounted((int) $data['counted'], (int) auth()->id(), $data['note'] ?? null);
                        \Filament\Notifications\Notification::make()
                            ->title('Saldo laci disesuaikan ke ' . self::rupiah($result['counted']))
                            ->body('Penyesuaian buku: ' . self::rupiah($result['adjustment']) . ' · saldo sistem sebelumnya: ' . self::rupiah($result['system_before']))
                            ->success()
                            ->send();
                    }),
            ])
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
