<?php

namespace App\Filament\Resources\CashBalances\Pages;

use App\Filament\Resources\CashBalances\CashBalanceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCashBalance extends ViewRecord
{
    protected static string $resource = CashBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
