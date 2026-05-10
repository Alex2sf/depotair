<?php

namespace App\Filament\Resources\OrderProducts\Pages;

use App\Filament\Resources\OrderProducts\OrderProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrderProduct extends CreateRecord
{
    protected static string $resource = OrderProductResource::class;
}
