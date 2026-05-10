<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Enums\ProductType;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with('inventory')
            ->where('is_enabled', true)
            ->orderBy('name')
            ->get()
            ->map(function ($product) {
                $inventory = $product->inventory;

                return [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'price'         => (int) $product->price,
                    'image_url'     => $product->image_url ?? null,
                    'product_type'  => $product->product_type?->value ?? 'UNKNOWN',
                    'unit'          => $product->unit,
                    'sku'           => $product->sku,
                    'stock'         => (int) ($inventory?->quantity ?? 0),
                    'low_stock'     => ($inventory?->quantity ?? 0) <= ($inventory?->low_stock_threshold ?? 10),
                ];
            });

        // FILTER OPTIONS — INI YANG KEREN!
        $typeOptions = collect(ProductType::cases())->map(fn($type) => [
            'value' => $type->value,
            'label' => $type->getLabel(),
        ])->prepend([
            'value' => 'SEMUA',
            'label' => 'Semua Tipe',
        ])->values()->toArray();

        return response()->json([
            'success' => true,
            'data' => $products,
            'filter_options' => [
                'product_types' => $typeOptions,
            ]
        ]);
    }

    public function store(Request $request)
    {
        // Validasi
        $request->validate([
            'name'  => 'required|string|max:100',
            'sku'   => 'nullable|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'unit'  => 'required|string', // Galon, Liter, kg, dll
            'initial_stock' => 'nullable|integer|min:0', // Stok awal opsional
        ]);

        // Simpan Produk
        $product = Product::create([
            'name' => $request->name,
            'sku'  => $request->sku ?? 'SKU-' . strtoupper(uniqid()), // Auto SKU kalau kosong
            'price' => $request->price,
            'unit' => $request->unit,
            'cogs' => 0, // Default HPP 0 dulu
            'is_enabled' => true,
            'product_type' => 'CONSUMABLE', // Default tipe
        ]);

        // Simpan Inventory Awal (Wajib ada biar muncul di stok)
        // Kita gunakan create() manual atau relation
        \App\Models\Inventory::create([
            'product_id' => $product->id,
            'quantity' => $request->initial_stock ?? 0,
            'low_stock_threshold' => 10,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Produk berhasil ditambahkan ke sistem!',
            'data' => $product
        ]);
    }
}