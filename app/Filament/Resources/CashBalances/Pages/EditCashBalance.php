<?php

namespace App\Filament\Resources\CashBalances\Pages;

use App\Filament\Resources\CashBalances\CashBalanceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCashBalance extends EditRecord
{
    protected static string $resource = CashBalanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
