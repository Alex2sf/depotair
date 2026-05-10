<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum OrderStatus: string implements HasLabel, HasColor, HasIcon
{
    case DRAFT = 'DRAFT';
    case PREPARED = 'PREPARED';
    case READY = 'READY';
    case ON_DELIVERY = 'ON_DELIVERY';
    case COMPLETE = 'COMPLETE';
    case CANCELLED = 'CANCELLED';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::PREPARED => 'Sedang Disiapkan',
            self::READY => 'Siap',
            self::ON_DELIVERY => 'Dalam Pengiriman',
            self::COMPLETE => 'Selesai',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::PREPARED => 'warning',
            self::READY => 'info',
            self::ON_DELIVERY => 'primary',
            self::COMPLETE => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::PREPARED => 'heroicon-o-clock',
            self::READY => 'heroicon-o-check-circle',
            self::ON_DELIVERY => 'heroicon-o-truck',
            self::COMPLETE => 'heroicon-o-shield-check',
            self::CANCELLED => 'heroicon-o-x-circle',
        };
    }

    /**
     * Get detailed description for each status
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::DRAFT => 'Pesanan dibuat, belum dikonfirmasi',
            self::PREPARED => 'Pesanan dikonfirmasi, sedang disiapkan staff',
            self::READY => 'Pesanan siap, menunggu driver/pelanggan',
            self::ON_DELIVERY => 'Pesanan sedang dalam perjalanan',
            self::COMPLETE => 'Pesanan selesai, pembayaran lunas',
            self::CANCELLED => 'Pesanan dibatalkan',
        };
    }

    /**
     * Check if status is terminal (cannot be changed)
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::COMPLETE, self::CANCELLED]);
    }

    /**
     * Check if status is cancellable
     */
    public function isCancellable(): bool
    {
        return !$this->isTerminal();
    }

    /**
     * Get valid next statuses from current status
     */
    public function getNextStatuses(): array
    {
        return match ($this) {
            self::DRAFT => [self::PREPARED, self::CANCELLED],
            self::PREPARED => [self::READY, self::CANCELLED],
            self::READY => [self::ON_DELIVERY, self::COMPLETE, self::CANCELLED],
            self::ON_DELIVERY => [self::COMPLETE, self::CANCELLED],
            self::COMPLETE => [],
            self::CANCELLED => [self::DRAFT],
        };
    }

    /**
     * Check if transition to another status is allowed
     */
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return in_array($newStatus, $this->getNextStatuses());
    }

    /**
     * Get all active statuses (not terminal)
     */
    public static function activeStatuses(): array
    {
        return [
            self::DRAFT,
            self::PREPARED,
            self::READY,
            self::ON_DELIVERY,
        ];
    }

    /**
     * Get statuses that require driver assignment
     */
    public static function requiresDriver(): array
    {
        return [
            self::ON_DELIVERY,
        ];
    }
}
