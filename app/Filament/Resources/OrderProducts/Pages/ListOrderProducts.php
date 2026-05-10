<?php

namespace App\Filament\Resources\OrderProducts\Pages;

    use App\Filament\Resources\OrderProducts\OrderProductResource;
    use Filament\Actions\CreateAction;
    use Filament\Resources\Pages\ListRecords;

    class ListOrderProducts extends ListRecords
    {
        protected static string $resource = OrderProductResource::class;

        protected function getHeaderActions(): array
        {
            return [
                CreateAction::make(),
            ];
        }
    }
