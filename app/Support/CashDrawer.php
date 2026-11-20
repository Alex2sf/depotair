<?php

namespace App\Support;

use App\Models\CashBalance;
use App\Models\CashTransaction;
use App\Models\Order;

/**
 * Pencatatan uang penjualan tunai yang masuk ke laci kasir.
 * Pesanan ambil sendiri: dicatat saat checkout. Pesanan antar (COD): dicatat saat pesanan selesai,
 * karena uangnya baru diterima dari kurir.
 */
class CashDrawer
{
    public static function hasSale(Order $order): bool
    {
        return CashTransaction::where('order_id', $order->id)
            ->where('type', CashTransaction::TYPE_DEPOSIT)
            ->where('description', 'like', 'Penjualan TUNAI%')
            ->exists();
    }

    /** Idempoten: pesanan yang uangnya sudah tercatat tidak dicatat dua kali. */
    public static function recordSale(Order $order, int $recordedBy): void
    {
        if (self::hasSale($order)) {
            return;
        }

        CashBalance::where('type', CashBalance::CASHIER)->lockForUpdate()->first();
        CashBalance::where('type', CashBalance::CASHIER)->increment('balance', $order->total_amount);

        $isCod = self::value($order->order_type) === 'DELIVERY';
        CashTransaction::create([
            'type'        => CashTransaction::TYPE_DEPOSIT,
            'cash_type'   => 'CASHIER',
            'amount'      => $order->total_amount,
            'description' => "Penjualan TUNAI #{$order->order_number}" . ($isCod ? ' (COD diterima dari kurir)' : ''),
            'recorded_by' => $recordedBy,
            'order_id'    => $order->id,
        ]);
    }

    public static function isCashDelivery(Order $order): bool
    {
        return self::value($order->payment_type) === 'TUNAI' && self::value($order->order_type) === 'DELIVERY';
    }

    private static function value($enumOrString): string
    {
        return is_object($enumOrString) ? (string) ($enumOrString->value ?? '') : (string) $enumOrString;
    }
}
