<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentType: string implements HasLabel
{
    case TUNAI    = 'TUNAI';
    case TRANSFER = 'TRANSFER';
    case QRIS     = 'QRIS';
    case CORPORATE = 'CORPORATE';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TUNAI    => 'Tunai',
            self::TRANSFER => 'Transfer Bank',
            self::QRIS     => 'QRIS',
            self::CORPORATE => 'Corporate',
        };
    }

    // Bonus: biar gampang dipake di validasi
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}