<?php

namespace App\Filament\Resources\CashierPurchases;

use App\Filament\Resources\CashierPurchases\Pages\ListCashierPurchases;
use App\Filament\Resources\CashierPurchases\Pages\ViewCashierPurchase;
use App\Filament\Resources\CashierPurchases\Schemas\CashierPurchaseInfolist;
use App\Filament\Resources\CashierPurchases\Tables\CashierPurchasesTable;
use App\Models\CashierPurchase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CashierPurchaseResource extends Resource
{
    protected static ?string $model = CashierPurchase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static string | \UnitEnum | null $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Belanja Kasir';

    protected static ?string $modelLabel = 'Belanja Kasir';

    protected static ?string $pluralModelLabel = 'Daftar Belanja Kasir';

    public static function infolist(Schema $schema): Schema
    {
        return CashierPurchaseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CashierPurchasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashierPurchases::route('/'),
            'view' => ViewCashierPurchase::route('/{record}'),
        ];
    }
}
