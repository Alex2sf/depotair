<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Enums\MovementReason;
use App\Filament\Resources\Inventories\InventoryResource;
use App\Support\StockAdjuster;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    private int $initialStock = 0;

    // Stok awal dicatat lewat riwayat gerakan stok, bukan ditulis langsung
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialStock = (int) ($data['quantity'] ?? 0);
        $data['quantity'] = 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->initialStock > 0) {
            StockAdjuster::setTo($this->record->product_id, $this->initialStock, auth()->id(), 'Stok awal (dibuat dari admin)', MovementReason::RESTOCK);
        }
    }
}
