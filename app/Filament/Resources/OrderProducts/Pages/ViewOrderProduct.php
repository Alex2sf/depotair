<?php

namespace App\Filament\Resources\OrderProducts\Pages;

use App\Filament\Resources\OrderProducts\OrderProductResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrderProduct extends ViewRecord
{
    protected static string $resource = OrderProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
