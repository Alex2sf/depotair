<?php

namespace App\Filament\Resources\OrderProducts;

use App\Filament\Resources\OrderProducts\Pages\CreateOrderProduct;
use App\Filament\Resources\OrderProducts\Pages\EditOrderProduct;
use App\Filament\Resources\OrderProducts\Pages\ListOrderProducts;
use App\Filament\Resources\OrderProducts\Pages\ViewOrderProduct;
use App\Filament\Resources\OrderProducts\Schemas\OrderProductForm;
use App\Filament\Resources\OrderProducts\Schemas\OrderProductInfolist;
use App\Filament\Resources\OrderProducts\Tables\OrderProductsTable;
use App\Models\OrderProduct;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OrderProductResource extends Resource
{
    protected static ?string $model = OrderProduct::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string | \UnitEnum | null $navigationGroup = 'Transaksi';

    protected static ?string $recordTitleAttribute = null;

    public static function form(Schema $schema): Schema
    {
        return OrderProductForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OrderProductInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OrderProductsTable::configure($table);
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
            'index' => ListOrderProducts::route('/'),
            'create' => CreateOrderProduct::route('/create'),
            'view' => ViewOrderProduct::route('/{record}'),
            'edit' => EditOrderProduct::route('/{record}/edit'),
        ];
    }
}
