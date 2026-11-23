<?php

namespace App\Filament\Resources\StockProcurements;

use App\Filament\Resources\StockProcurements\Pages\ListStockProcurements;
use App\Filament\Resources\StockProcurements\Pages\ViewStockProcurement;
use App\Models\StockProcurement;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar pengajuan barang (hanya-baca). Persetujuan dan penerimaan tetap lewat app,
 * karena di sana Kas Besar dipotong dan stok ditambah beserta riwayatnya.
 */
class StockProcurementResource extends Resource
{
    protected static ?string $model = StockProcurement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string | \UnitEnum | null $navigationGroup = 'Inventori';

    protected static ?string $navigationLabel = 'Pengajuan Barang';

    protected static ?string $modelLabel = 'Pengajuan Barang';

    protected static ?string $pluralModelLabel = 'Pengajuan Barang';

    protected static ?string $slug = 'pengajuan-barang';

    public const STATUS_LABELS = [
        'PENDING' => 'Menunggu persetujuan',
        'APPROVED' => 'Disetujui, belum diterima',
        'RECEIVED' => 'Selesai (barang diterima)',
        'REJECTED' => 'Ditolak',
    ];

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'PENDING' => 'warning',
            'APPROVED' => 'info',
            'RECEIVED' => 'success',
            'REJECTED' => 'danger',
            default => 'gray',
        };
    }

    private static function rupiah($value): string
    {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['requester', 'approver', 'receiver'])->withCount('items'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('procurement_number')
                    ->label('No. Pengajuan')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Tanggal Diajukan')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('requester.name')
                    ->label('Diajukan Oleh')
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => self::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (?string $state) => self::statusColor($state)),
                TextColumn::make('items_count')
                    ->label('Jumlah Barang')
                    ->alignRight(),
                TextColumn::make('total_estimated_amount')
                    ->label('Total Diajukan')
                    ->formatStateUsing(fn ($state) => self::rupiah($state))
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('total_approved_amount')
                    ->label('Total Disetujui')
                    ->formatStateUsing(fn ($state, $record) => in_array($record->status, ['APPROVED', 'RECEIVED'], true) ? self::rupiah($state) : '-')
                    ->alignRight()
                    ->sortable(),
                TextColumn::make('approved_at')
                    ->label('Tgl Disetujui/Ditolak')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('received_at')
                    ->label('Tgl Barang Diterima')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(self::STATUS_LABELS),
                \App\Filament\Filters\PeriodeFilter::make('created_at', 'created_at', 'Tanggal Diajukan'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        // Tampilan detail kustom (ringkasan, alur status, tabel barang, catatan) dengan CSS tertanam
        return $schema->components([
            ViewEntry::make('detail')
                ->hiddenLabel()
                ->columnSpanFull()
                ->view('filament.infolists.stock-procurement-detail'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockProcurements::route('/'),
            'view' => ViewStockProcurement::route('/{record}'),
        ];
    }

    // Hanya-baca: membuat, mengubah, dan menghapus pengajuan tetap lewat app.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
