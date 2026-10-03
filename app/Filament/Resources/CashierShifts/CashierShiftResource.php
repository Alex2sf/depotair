<?php

namespace App\Filament\Resources\CashierShifts;

use App\Filament\Resources\CashierShifts\Pages\ListCashierShifts;
use App\Filament\Resources\CashierShifts\Pages\ViewCashierShift;
use App\Filament\Resources\CashierShifts\Schemas\CashierShiftInfolist;
use App\Filament\Resources\CashierShifts\Tables\CashierShiftsTable;
use App\Models\CashierShift;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashierShiftResource extends Resource
{
    protected static ?string $model = CashierShift::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string | \UnitEnum | null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Shift Kasir';

    protected static ?string $modelLabel = 'Shift Kasir';

    protected static ?string $pluralModelLabel = 'Daftar Shift Kasir';

    public static function infolist(Schema $schema): Schema
    {
        return CashierShiftInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierShiftsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierShifts::route('/'),
            'view' => ViewCashierShift::route('/{record}'),
        ];
    }
}
