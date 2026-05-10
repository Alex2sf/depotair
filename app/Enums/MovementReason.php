<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum MovementReason: string implements HasLabel, HasColor, HasIcon
{
    case RESTOCK = 'RESTOCK';
    case SALE = 'SALE';
    case DAMAGE = 'DAMAGE';
    case RETURN = 'RETURN';
    case ADJUSTMENT = 'ADJUSTMENT';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::RESTOCK => 'Tambah Stok',
            self::SALE => 'Penjualan',
            self::DAMAGE => 'Kerusakan',
            self::RETURN => 'Pengembalian',
            self::ADJUSTMENT => 'Penyesuaian',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::RESTOCK => 'success',   // Green - positive
            self::SALE => 'primary',      // Blue - neutral
            self::DAMAGE => 'danger',     // Red - negative
            self::RETURN => 'warning',    // Yellow - requires attention
            self::ADJUSTMENT => 'info',   // Info - manual change
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::RESTOCK => 'heroicon-o-arrow-up-tray',
            self::SALE => 'heroicon-o-shopping-cart',
            self::DAMAGE => 'heroicon-o-exclamation-triangle',
            self::RETURN => 'heroicon-o-arrow-uturn-left',
            self::ADJUSTMENT => 'heroicon-o-pencil-square',
        };
    }

    /**
     * Get description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::RESTOCK => 'Penambahan stok dari supplier',
            self::SALE => 'Pengurangan stok karena penjualan',
            self::DAMAGE => 'Pengurangan stok karena kerusakan/kadaluarsa',
            self::RETURN => 'Penambahan stok dari pengembalian pelanggan',
            self::ADJUSTMENT => 'Penyesuaian manual oleh admin',
        };
    }

    /**
     * Check if this reason typically increases stock
     */
    public function isPositiveMovement(): bool
    {
        return in_array($this, [self::RESTOCK, self::RETURN]);
    }

    /**
     * Check if this reason typically decreases stock
     */
    public function isNegativeMovement(): bool
    {
        return in_array($this, [self::SALE, self::DAMAGE]);
    }

    /**
     * Check if this reason requires manual input
     */
    public function requiresManualEntry(): bool
    {
        return in_array($this, [self::RESTOCK, self::DAMAGE, self::ADJUSTMENT]);
    }

    /**
     * Check if this reason is automatically triggered
     */
    public function isAutomatic(): bool
    {
        return $this === self::SALE;
    }

    /**
     * Get reasons that can be used for manual stock adjustments
     */
    public static function manualReasons(): array
    {
        return [
            self::RESTOCK,
            self::DAMAGE,
            self::RETURN,
            self::ADJUSTMENT,
        ];
    }

    /**
     * Get expected quantity change direction
     */
    public function getExpectedDirection(): string
    {
        return match ($this) {
            self::RESTOCK, self::RETURN => 'positive',
            self::SALE, self::DAMAGE => 'negative',
            self::ADJUSTMENT => 'both',
        };
    }
}
