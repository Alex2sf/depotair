<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CustomerOrderController extends Controller
{
    public function home(Request $request)
    {
        $query = Product::where('is_enabled', true);

        if ($request->has('search') && $request->search != '') {
             $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('type') && $request->type != 'ALL') {
             $query->where('product_type', $request->type);
        }

        $products = $query->with('inventory')->get();
        return view('customer.home', compact('products'));
    }

    public function addToCart(Request $request)
    {
        $productId = $request->product_id;
        $qty = $request->qty ?? 1;

        $product = Product::with('inventory')->findOrFail($productId);
        $stock = $product->inventory->quantity ?? 0;

        $cart = session('cart', []);
        $currentQty = isset($cart[$productId]) ? $cart[$productId]['qty'] : 0;
        $newQty = $currentQty + $qty;

        if ($newQty > $stock) {
            return redirect()->back()->with('error', "Stok tidak cukup! Sisa stok: $stock");
        }

        if (isset($cart[$productId])) {
            $cart[$productId]['qty'] = $newQty;
        } else {
            $cart[$productId] = [
                'name' => $product->name,
                'price' => $product->price,
                'image' => $product->image_url,
                'qty' => $qty
            ];
        }

        session(['cart' => $cart]);

        if ($request->has('checkout_now')) {
            return redirect()->route('checkout');
        }

        return redirect()->back()->with('success', 'Ditambah ke keranjang!');
    }

    public function cart()
    {
        $cart = session('cart', []);
        $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['qty']);
        return view('customer.cart', compact('cart', 'subtotal'));
    }

    public function updateCart(Request $request)
    {
        $cart = session('cart', []);
        foreach ($request->qty as $id => $qty) {
            if ($qty <= 0) {
                unset($cart[$id]);
            } else {
                $product = Product::with('inventory')->find($id);
                $stock = $product->inventory->quantity ?? 0;

                if ($qty > $stock) {
                    return redirect()->back()->with('error', "Stok {$product->name} tidak cukup! Sisa: $stock");
                }
                
                $cart[$id]['qty'] = $qty;
            }
        }
        session(['cart' => $cart]);
        return redirect()->back()->with('success', 'Keranjang diperbarui');
    }

    public function removeFromCart($id)
    {
        $cart = session('cart', []);
        unset($cart[$id]);
        session(['cart' => $cart]);
        return redirect()->back()->with('success', 'Item dihapus');
    }

    public function checkout()
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect('/');
        }
        $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
        return view('customer.checkout', compact('cart', 'subtotal'));
    }

    public function submitOrder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string', // Address nullable if pickup, handled below
            'notes' => 'nullable|string',
            'order_type' => 'required|in:DELIVERY,SELF_PICKUP',
            'payment_type' => 'required|in:TUNAI,TRANSFER,QRIS,CORPORATE',
            'address_link' => 'nullable|url',
            'delivery_scheduled_at' => 'nullable|date',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect('/')->with('error', 'Keranjang kosong!');
        }

        $subtotal = collect($cart)->sum(fn($i) => $i['price'] * $i['qty']);
        $total = $subtotal; // bisa tambah ongkir nanti

        // 1. Buat atau cari customer
        $customer = Customer::firstOrCreate(
            ['phone_number' => $request->phone],
            ['name' => $request->name, 'address' => $request->address]
        );

        // 2. Buat order
       $order = Order::create([
        'order_number'     => strtoupper(Str::random(12)),
        'customer_id'      => $customer->id,
        'status'           => 'DRAFT',
        'order_type'       => $request->order_type,
        'payment_type'     => $request->payment_type,
        'delivery_address' => $request->order_type === 'DELIVERY' ? $request->address : null,
        'address_link'     => $request->address_link,
        'delivery_scheduled_at' => $request->delivery_scheduled_at,
        'subtotal'         => $subtotal,
        'total_amount'     => $total,
        'notes'            => $request->notes,
        ]);

        // 3. Simpan item ke pivot
        foreach ($cart as $id => $item) {
            OrderProduct::create([
                'order_id' => $order->id,
                'product_id' => $id,
                'product_name' => $item['name'],
                'quantity' => $item['qty'],
                'price_at_sale' => $item['price'],
                'cogs_at_sale' => Product::find($id)->cogs ?? 0,
                'subtotal' => $item['price'] * $item['qty'],
            ]);
        }

        // 4. Kosongin keranjang
        session()->forget('cart');

        // Redirect ke Home dengan session success_order untuk trigger Popup
        return redirect()->route('customer.home')->with('success_order', $order);
    }

    public function success($order_number)
    {
        $order = Order::where('order_number', $order_number)->firstOrFail();
        return view('customer.success', compact('order'));
    }
    public function trackForm()
{
    return view('customer.track');
}

public function trackOrder(Request $request)
{
    $request->validate([
        'order_number' => 'required|string|max:20'
    ]);

    $order = Order::with(['customer', 'products'])  // INI YANG BENAR
        ->where('order_number', strtoupper($request->order_number))
        ->first();

    if (!$order) {
        return back()->withErrors(['order_number' => 'Nomor pesanan tidak ditemukan!']);
    }

    return view('customer.track_result', compact('order'));
}
}