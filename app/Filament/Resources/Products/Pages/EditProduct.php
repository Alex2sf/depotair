<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (isset($data['image_url']) && $data['image_url']) {
            if (filter_var($data['image_url'], FILTER_VALIDATE_URL)) {
                $data['image_source_type'] = 'url';
                $data['image_url_link'] = $data['image_url'];
            } else {
                $data['image_source_type'] = 'upload';
                $data['image_upload'] = $data['image_url'];
            }
        } else {
            $data['image_source_type'] = 'upload';
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['image_url'] = match ($data['image_source_type'] ?? 'upload') {
            'upload' => $data['image_upload'] ?? null,
            'url' => $data['image_url_link'] ?? null,
            default => null,
        };

        unset($data['image_source_type']);
        unset($data['image_upload']);
        unset($data['image_url_link']);

        return $data;
    }
}
