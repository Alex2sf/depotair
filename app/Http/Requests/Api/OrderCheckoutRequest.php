<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
// --- Penambahan Import Class yang digunakan ---
use App\Models\Product; // Model yang digunakan di dalam withValidator
use App\Models\Inventory; // Model yang digunakan di dalam withValidator

class OrderCheckoutRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'customer_id'           => 'required|exists:customers,id',
            'order_type'            => 'required|in:SELF_PICKUP,DELIVERY',
            'payment_type'          => 'required|in:TUNAI,TRANSFER,QRIS,CORPORATE',

            // INI YANG BENAR & PALING AMAN!
            'delivery_address'      => 'required_if:order_type,DELIVERY|nullable|string|max:500',
            'delivery_scheduled_at' => 'required_if:order_type,DELIVERY|nullable|date',

            'delivery_fee'          => 'nullable|numeric|min:0',
            'additional_fee'        => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string|max:500',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'required|exists:products,id',
            'items.*.quantity'      => 'required|integer|min:1',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            foreach ($items as $index => $item) {
                $productId = $item['product_id'];
                $qtyDiminta = $item['quantity'];

                // Menggunakan Product::find (sudah di-import)
                $product = Product::find($productId);
                if (!$product) {
                    $this->validator->errors()->add(
                        "items.{$index}.product_id",
                        "Produk tidak ditemukan."
                    );
                    continue;
                }

                // Menggunakan Inventory::where (sudah di-import)
                $stokSekarang = Inventory::where('product_id', $productId)->first()?->quantity ?? 0;

                if ($qtyDiminta > $stokSekarang) {
                    $this->validator->errors()->add(
                        "items.{$index}.quantity",
                        "Stok {$product->name} tidak cukup! Tersisa: {$stokSekarang}, diminta: {$qtyDiminta}"
                    );
                }
            }
        });
    }
}