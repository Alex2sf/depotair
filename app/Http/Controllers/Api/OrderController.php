<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OrderCheckoutRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDeliveryProof;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\CashBalance;
use App\Models\CashTransaction;

// INI YANG WAJIB ADA BIAR GAK ERROR LAGI
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentType;
class OrderController extends Controller
{

    // ==================== RIWAYAT TRANSAKSI ====================
    public function history(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'search' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $query = Order::query()->with(['customer', 'staff', 'products']);
        
        if ($user->role === 'customer') {
            $query->where('customer_id', $user->id);
        }
        
        $query->whereIn('status', ['DRAFT', 'PREPARED', 'READY', 'ON_DELIVERY', 'COMPLETE']);
        
        $query->when($request->search, function ($q) use ($request) {
            $search = $request->search;
            $q->where('order_number', 'like', "%{$search}%")
              ->orWhereHas('customer', fn($c) =>
                  $c->where('phone_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
              );
        });
        
        $query->when($request->start_date, fn($q) => $q->whereDate('created_at', '>=', $request->start_date))
              ->when($request->end_date, fn($q) => $q->whereDate('created_at', '<=', $request->end_date));
        
        $query->orderByDesc('created_at');
        
        if (!$request->hasAny(['start_date', 'end_date', 'search']) && $user->role !== 'customer') {
            $query->whereDate('created_at', today());
        }
        
        $orders = $query->paginate(30);
        
        $data = collect($orders->items())->map(function ($order) {
            return [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer_name' => $order->customer?->name ?? 'Pelanggan',
                'customer_phone' => $order->customer?->phone_number ?? '-',
                'gmaps_url' => $order->address_link, // ← TAMBAH INI KALAU MAU
                'total_amount' => (int) $order->total_amount,
                'order_type' => $order->order_type,
                'payment_type' => $order->payment_type,
                'status' => $order->status,
                'items_count' => $order->products->count(),
                'created_at' => $order->created_at->toIso8601String(),
                'completed_at' => $order->completed_time?->toIso8601String(),
                'delivery_scheduled_at' => $order->delivery_scheduled_at?->format('d/m/Y H:i') ?? 'Sekarang', 
                'ready_time' => $order->ready_time?->toIso8601String(), 
                'delivery_time' => $order->delivery_time?->toIso8601String(),
                'completed_target_at' => $order->completed_target_at?->toIso8601String(), // FIX: Global Timer
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
            ]
        ]);
    }
    // ==================== DETAIL ORDER ====================
   public function show($order_number)
{
    $order = Order::with([
        'customer',
        'staff',
        'courier',
        'products' => fn($q) => $q->select('products.id', 'products.name', 'order_product.quantity', 'order_product.price_at_sale', 'order_product.subtotal'),
        'deliveryProof.uploadedBy'  // Load bukti + user yang upload
    ])
    ->where('order_number', $order_number)
    ->firstOrFail();

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Pesanan tidak ditemukan.'
        ], 404);
    }


    $response = [
        'success' => true,
        'order' => [
            'order_number'     => $order->order_number,
            'status'           => $order->status->value ?? $order->status,
            'order_type'       => $order->order_type->value ?? $order->order_type,
            'payment_type'     => $order->payment_type->value ?? $order->payment_type,
            'total_amount'     => (int) $order->total_amount,
            'delivery_fee'     => (int) $order->delivery_fee,
            'subtotal'         => (int) $order->subtotal,
            'notes'            => $order->notes,
            'ready_time'       => $order->ready_time?->format('d M Y H:i'),
            'delivery_time'    => $order->delivery_time?->format('d M Y H:i'),
            'completed_time'   => $order->completed_time?->format('d M Y H:i'),
            'customer' => [
                'id'     => $order->customer->id,
                'name'   => $order->customer->name,
                'phone'  => $order->customer->phone_number,
                'address'=> $order->delivery_address ?? $order->customer->address,
                'gmaps_url'  => $order->address_link, // ← INI YANG DIPAKE KURIR BUKA MAPS!
            ],
            'staff' => $order->staff ? $order->staff->name : null,
            'courier' => $order->courier ? $order->courier->name : null,
            'completed_target_at' => $order->completed_target_at?->toIso8601String(), // EXPOSE GLOBAL TARGET
            'items' => $order->products->map(fn($p) => [
                'id'       => $p->id,
                'name'     => $p->name,
                'quantity' => (int) $p->pivot->quantity,
                'price'    => (int) $p->pivot->price_at_sale,
                'subtotal' => (int) $p->pivot->subtotal,
            ]),
            'delivery_proof' => null,  // Default null
        ]
    ];

    // TAMBAH BUKTI KALAU ADA
    if ($order->status === OrderStatus::COMPLETE 
    && $order->order_type === OrderType::DELIVERY 
    && $order->hasDeliveryProof()) {
    
    $proof = $order->deliveryProof;

    $response['order']['delivery_proof'] = [
        'image_url'     => $proof->image_url,  // PAKE YANG ASLI AJA! (misal: /storage/delivery_proof/xxx.jpg)
        'notes'         => $proof->notes ?? 'Tidak ada catatan',
        'uploaded_by'   => $proof->uploadedBy->name ?? 'Kurir',
        'uploaded_at'   => $proof->created_at->format('d M Y H:i'),
    ];
}

    return response()->json($response);
}
    public function checkout(OrderCheckoutRequest $request)
        {
            \Log::info('CHECKOUT DIPANGGIL BRO! PAYMENT: ' . $request->payment_type);
            $kasir = $request->user();
            $response = DB::transaction(function () use ($request, $kasir) {
                // TAMBAH LOGIC JADWAL ANTER
                $deliveryScheduledAt = null;
                if ($request->order_type === 'DELIVERY') {
                    $deliveryScheduledAt = $request->filled('delivery_scheduled_at')
                        ? Carbon::parse($request->delivery_scheduled_at)
                        : now(); 
                }
                
                // --- LOGIC BARU: GLOBAL TIMER ---
                $completedTargetAt = $request->filled('prepared_minutes') 
                    ? now()->addMinutes((int)$request->prepared_minutes) 
                    : null;

                // 1. Buat order
                $order = Order::create([
                    'customer_id' => $request->customer_id,
                    'staff_id' => $kasir->id,
                    'order_type' => $request->order_type,
                    'payment_type' => $request->payment_type,
                    'delivery_address' => $request->order_type === 'DELIVERY' ? $request->delivery_address : null,
                    'address_link'     => $request->address_link ?? null, 
                    'latitude' => $request->latitude ?? null,
                    'longitude' => $request->longitude ?? null,
                    'delivery_fee' => $request->delivery_fee ?? 0,
                    'additional_fee' => $request->additional_fee ?? 0,
                    'notes' => $request->notes,
                    'delivery_scheduled_at' => $deliveryScheduledAt, 
                    'completed_target_at' => $completedTargetAt, // SATU TARGET UNTUK SEMUA
                    'status' => 'DRAFT',
                ]);

                // SYNC ALAMAT KE CUSTOMER (REQUESTED FEATURE)
                if ($request->order_type === 'DELIVERY' && $request->filled('delivery_address')) {
                    \App\Models\Customer::where('id', $request->customer_id)
                        ->update(['address' => $request->delivery_address]);
                }
    
                $subtotal = 0;
                foreach ($request->items as $item) {
                    $product = Product::with('inventory')->findOrFail($item['product_id']);
                    if (!$product->is_enabled) {
                        throw new \Exception("Produk {$product->name} tidak aktif.");
                    }
                    $qty = $item['quantity'];
                    $lineTotal = $product->price * $qty;
                    $subtotal += $lineTotal;
                    $order->products()->attach($product->id, [
                        'product_name' => $product->name,
                        'quantity' => $qty,
                        'price_at_sale' => $product->price,
                        'cogs_at_sale' => $product->cogs,
                        'subtotal' => $lineTotal,
                    ]);
                    $product->inventory->deductStock($qty, $order);
                }
                $order->subtotal = $subtotal;
                $order->total_amount = $subtotal + ($request->delivery_fee ?? 0) + ($request->additional_fee ?? 0);
                
                // STATUS FINAL LOGIC
                if ($request->order_type === 'DELIVERY') {
                    $order->status = 'DRAFT'; 
                } else {
                    $order->status = 'READY';
                }
                $order->save(); 
    
                // INI YANG BARU — PASTI AMBIL order_number TERBARU!
                $order = Order::with(['products', 'customer'])->findOrFail($order->id);
                \Log::info('ORDER FINAL - ID: ' . $order->id . ' | NOMOR: ' . $order->order_number);
    
               // TUNAI MASUK KAS OTOMATIS + DEBUG LEVEL DEWA
                if (($order->payment_type->value ?? '') === 'TUNAI') {
                    $saldoSebelum = CashBalance::where('type', CashBalance::CASHIER)->first()->balance ?? 0;
                    CashBalance::where('type', CashBalance::CASHIER)
                        ->increment('balance', $order->total_amount);
                    $saldoSesudah = CashBalance::where('type', CashBalance::CASHIER)->first()->balance ?? 0;
                    try {
                        $cash = CashTransaction::create([
                            'type' => CashTransaction::TYPE_DEPOSIT,
                            'amount' => $order->total_amount,
                            'description' => "Penjualan TUNAI #{$order->order_number}",
                            'recorded_by' => $kasir->id,
                            'order_id' => $order->id,
                        ]);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                return [
                    'order_number' => $order->order_number,
                    'total_amount' => $order->total_amount,
                    'payment_type' => $order->payment_type,
                    'customer_name' => $order->customer->name ?? 'Pelanggan',
                    'completed_target_at' => $order->completed_target_at, // Return juga
                    'items' => $order->products->map(fn($p) => [
                        'name' => $p->pivot->product_name,
                        'quantity' => $p->pivot->quantity,
                        'price' => $p->pivot->price_at_sale,
                        'subtotal' => $p->pivot->subtotal,
                    ])->toArray(),
                ];
            });
            return response()->json([
                'success' => true,
                'message' => 'Order berhasil + Global Timer active!',
                'order' => $response
            ]);
        }
    // ==================== KASIR SELESAIKAN PESANAN (PICKUP atau DELIVERY yang belum diambil) ====================
    public function completeOrderManual($order_number, Request $request)
    {
        $kasir = $request->user();

        $order = Order::where('order_number', $order_number)
            ->whereIn('status', ['READY', 'ON_DELIVERY'])
            ->firstOrFail();

        // Jika DELIVERY dan sudah diambil kurir → TAPI KASIR MAU PAKSA SELESAI (Req User)
        // if ($order->order_type === 'DELIVERY' && $order->courier_id !== null) { ... }

        $order->update([
            'status'         => 'COMPLETE',
            'completed_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diselesaikan oleh kasir!',
            'order_number'   => $order->order_number,
        ]);
    }

    public function cancelOrder($order_number, Request $request)
    {
        try {
            $order = Order::with('products')->where('order_number', $order_number)
                ->whereIn('status', ['DRAFT', 'NEW', 'PENDING', 'PAID', 'PREPARED', 'READY', 'ON_DELIVERY']) // Izinkan status lain selain READY
                ->firstOrFail();

            DB::transaction(function () use ($order, $request) {
                // 1. Kembalikan stok ke tabel inventories
                foreach ($order->products as $product) {
                    Inventory::where('product_id', $product->id)
                        ->increment('quantity', $product->pivot->quantity);
                }

                // 2. Kalau bayar TUNAI → refund dari kas kasir
            if (($order->payment_type->value ?? '') === 'TUNAI') {
                    CashBalance::where('type', CashBalance::CASHIER)
                        ->decrement('balance', $order->total_amount);

                    CashTransaction::create([
                        'type'        => CashTransaction::TYPE_EXPENSE,
                        'amount'      => $order->total_amount,
                        'description' => "Refund batal order #{$order->order_number}",
                        'recorded_by' => $request->user()->id,
                        'order_id'    => $order->id,
                    ]);
                }

                // 3. Ubah status
                $order->update(['status' => 'CANCELLED']);
            });

            return response()->json([
                'success' => true,
                'message' => 'Order berhasil dibatalkan + uang TUNAI dikembalikan!'
            ]);

        } catch (\Exception $e) {
            \Log::error('Cancel order gagal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
    
    // Di dalam class OrderController

    public function markAsReady($order_number)
    {
        $user = auth()->user();
    
        $order = Order::where('order_number', $order_number)
            ->where(function($q) {
                $q->whereIn('status', ['DRAFT', 'PREPARED', 'PENDING', 'NEW', 'PAID'])
                  ->orWhereRaw('LOWER(status) IN ("draft", "prepared", "pending", "new", "paid")');
            })
            ->first();
    
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak ditemukan atau status tidak valid.'
            ], 404);
        }
    
        $order->update([
            'status'    => 'READY',
            'staff_id'  => $user->id,
            'ready_time'  => now(),
        ]);
    
        return response()->json([
            'success' => true,
            'message' => "Pesanan #{$order->order_number} sudah SIAP!",
            'status'   => 'READY'
        ]);
    }
    
}