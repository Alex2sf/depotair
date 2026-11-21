<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditInventory extends EditRecord
{
    protected static string $resource = InventoryResource::class;

    private ?int $targetStock = null;

    // Stok sistem boleh diedit, tapi perubahannya dicatat sebagai Penyesuaian di riwayat stok (bukan ditimpa diam-diam)
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('quantity', $data)) {
            $this->targetStock = (int) $data['quantity'];
            unset($data['quantity']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        if ($this->targetStock !== null) {
            \App\Support\StockAdjuster::setTo($this->record->product_id, $this->targetStock, auth()->id(), 'Edit stok dari admin');
            $this->record->refresh();
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
