<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum ProductType: string implements HasLabel, HasColor, HasIcon
{
    case REFILL = 'REFILL';      // Ubah ke uppercase match migration
    case NEW_UNIT = 'NEW_UNIT';  // Ubah ke uppercase match migration
    case CONSUMABLE = 'CONSUMABLE';  // Ubah ke uppercase match migration

    public function getLabel(): ?string
    {
        return match ($this) {
            self::REFILL => 'Isi Ulang',
            self::NEW_UNIT => 'Unit Baru (Galon + Isi)',
            self::CONSUMABLE => 'Barang Konsumsi',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::REFILL => 'info',      // Blue
            self::NEW_UNIT => 'success',  // Green
            self::CONSUMABLE => 'warning', // Yellow
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::REFILL => 'heroicon-o-arrow-path',
            self::NEW_UNIT => 'heroicon-o-plus-circle',
            self::CONSUMABLE => 'heroicon-o-shopping-bag',
        };
    }

    /**
     * Check if this product type requires gallon balance
     */
    public function requiresGallonBalance(): bool
    {
        return $this === self::REFILL;
    }

    /**
     * Check if this product type adds to gallon balance
     */
    public function addsGallonBalance(): bool
    {
        return $this === self::NEW_UNIT;
    }

    /**
     * Get description for each type
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::REFILL => 'Isi ulang galon - memerlukan galon kosong',
            self::NEW_UNIT => 'Galon baru + isi air - menambah saldo galon',
            self::CONSUMABLE => 'Produk konsumsi lainnya (tissue, gelas, dll)',
        };
    }

    /**
     * Get all values as array
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all labels as array
     */
    public static function labels(): array
    {
        return array_map(fn($case) => $case->getLabel(), self::cases());
    }
}
