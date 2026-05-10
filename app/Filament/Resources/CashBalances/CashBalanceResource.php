<?php

namespace App\Filament\Resources\CashBalances;

use App\Filament\Resources\CashBalances\Pages\CreateCashBalance;
use App\Filament\Resources\CashBalances\Pages\EditCashBalance;
use App\Filament\Resources\CashBalances\Pages\ListCashBalances;
use App\Filament\Resources\CashBalances\Pages\ViewCashBalance;
use App\Filament\Resources\CashBalances\Schemas\CashBalanceForm;
use App\Filament\Resources\CashBalances\Schemas\CashBalanceInfolist;
use App\Filament\Resources\CashBalances\Tables\CashBalancesTable;
use App\Models\CashBalance;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashBalanceResource extends Resource
{
    protected static ?string $model = CashBalance::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'Keuangan';

    protected static ?string $recordTitleAttribute = 'type';

    public static function form(Schema $schema): Schema
    {
        return CashBalanceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CashBalanceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashBalancesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashBalances::route('/'),
            'create' => CreateCashBalance::route('/create'),
            'view' => ViewCashBalance::route('/{record}'),
            'edit' => EditCashBalance::route('/{record}/edit'),
        ];
    }
}
