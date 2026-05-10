<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum OrderType: string implements HasLabel, HasColor, HasIcon
{
    case DELIVERY = 'DELIVERY';
    case SELF_PICKUP = 'SELF_PICKUP';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DELIVERY => 'Pengiriman',
            self::SELF_PICKUP => 'Self Pickup',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::DELIVERY => 'primary',    // Biru
            self::SELF_PICKUP => 'success',    // Hijau
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DELIVERY => 'heroicon-o-truck',
            self::SELF_PICKUP => 'heroicon-o-shopping-bag', // Lebih cocok buat takeaway
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DELIVERY => 'Pesanan akan diantar ke alamat pelanggan',
            self::SELF_PICKUP => 'Pelanggan mengambil sendiri pesanan di depot',
        };
    }

    public function requiresDeliveryFee(): bool
    {
        return $this === self::DELIVERY;
    }

    public function requiresDriver(): bool
    {
        return $this === self::DELIVERY;
    }

    public function requiresDeliveryAddress(): bool
    {
        return $this === self::DELIVERY;
    }

    public function getWorkflowStatuses(): array
    {
        return match ($this) {
            self::DELIVERY => [
                OrderStatus::DRAFT,
                OrderStatus::PREPARED,
                OrderStatus::READY,
                OrderStatus::ON_DELIVERY,
                OrderStatus::COMPLETE,
            ],
            self::SELF_PICKUP => [
                OrderStatus::DRAFT,
                OrderStatus::PREPARED,
                OrderStatus::READY,
                OrderStatus::COMPLETE,
            ],
        };
    }
}