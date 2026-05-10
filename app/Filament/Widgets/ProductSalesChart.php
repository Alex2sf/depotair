<?php

namespace App\Filament\Widgets;

use Illuminate\Support\Facades\DB;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class ProductSalesChart extends ChartWidget
{
    protected ?string $heading = 'Top 5 Produk Terlaris (Bulan Ini)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // Query Top 5 Products
        $topProducts = DB::table('order_product')
            ->join('orders', 'orders.id', '=', 'order_product.order_id')
            ->join('products', 'products.id', '=', 'order_product.product_id')
            ->whereBetween('orders.created_at', [$startOfMonth, $endOfMonth])
            ->where('orders.status', '!=', 'CANCEL')
            ->select('products.name', DB::raw('SUM(order_product.quantity) as total_qty'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Terjual (Qty)',
                    'data' => $topProducts->pluck('total_qty')->toArray(),
                    'backgroundColor' => [
                        '#10b981', // emerald-500
                        '#3b82f6', // blue-500
                        '#f59e0b', // amber-500
                        '#ef4444', // red-500
                        '#8b5cf6', // violet-500
                    ],
                ],
            ],
            'labels' => $topProducts->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // or 'pie', 'doughnut'
    }
}
