<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
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
