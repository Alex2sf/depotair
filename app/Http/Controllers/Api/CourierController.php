<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDeliveryProof;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CourierController extends Controller
{
    // ==================== LIST PESANAN UNTUK KURIR ====================
    // ==================== LIST PESANAN UNTUK KURIR ====================
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|in:DRAFT,READY,ON_DELIVERY',
            'page' => 'nullable|integer|min:1',
        ]);
        $kurir = $request->user();
        $query = Order::with(['customer', 'products'])
            ->where('order_type', 'DELIVERY')
            ->whereIn('status', ['DRAFT', 'READY', 'ON_DELIVERY'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->date, function($q) use ($request) {
                if ($request->date === 'today') {
                    $q->where(function($sub) {
                        $sub->whereDate('delivery_scheduled_at', today())
                            ->orWhere(function($sub2) {
                                $sub2->whereNull('delivery_scheduled_at')
                                     ->whereDate('created_at', today());
                            });
                    });
                }
            })
            // HAPUS FILTER INI AGAR KURIR BISA LIHAT SEMUA ORDER (READY/ON_DELIVERY)
            // ->when($kurir->role === 'kurir', fn($q) => $q->where('courier_id', $kurir->id))
            ->orderByRaw('delivery_scheduled_at IS NULL')
            ->orderBy(DB::raw('DATE(delivery_scheduled_at)'), 'desc')
            ->orderBy(DB::raw('TIME(delivery_scheduled_at)'), 'asc');
        $perPage = 30;
        $orders = $query->paginate($perPage);
        $data = $orders->map(fn($o) => [
            'id' => $o->id,
            'order_number' => $o->order_number,
            'tanggal' => $o->created_at->format('d/m/Y H:i'),
            'customer' => $o->customer?->name ?? 'Walk-in',
            'phone' => $o->customer?->phone_number ?? '-',
            'alamat' => $o->delivery_address ?? '-',
            'status' => $o->status,
            'total' => (int) $o->total_amount,
            'items_count' => $o->products->count(),
            'delivery_scheduled_at' => $o->delivery_scheduled_at?->format('d/m/Y H:i') ?? 'Sekarang',
            // ADDED FOR TIMER:
            'created_at' => $o->created_at->toIso8601String(),
            'ready_time' => $o->ready_time?->toIso8601String(),
            'delivery_time' => $o->delivery_time?->toIso8601String(),
            'completed_target_at' => $o->completed_target_at?->toIso8601String(), // FIX: Global Timer for Courier
        ]);
        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $perPage,
            ]
        ]);
    }

    // ==================== DETAIL PESANAN ====================
// ==================== DETAIL PESANAN (UNTUK KURIR) ====================
    public function show($order_number)
    {
        $kurir = request()->user();

        $order = Order::with(['customer', 'products', 'deliveryProof'])
            ->where('order_number', $order_number)
            ->where('order_type', 'DELIVERY')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'order' => [
                'order_number'         => $order->order_number,
                'tanggal'               => $order->created_at->format('d/m/Y H:i'),
                'customer'              => $order->customer?->name ?? 'Walk-in',
                'phone'                 => $order->customer?->phone_number ?? '-',
                'alamat'                => $order->delivery_address ?? '-',
                'gmaps_url'             => $order->address_link, // INI YANG DIPAKE KURIR KLIK!
                'status'                => $order->status,
                'total'                 => (int) $order->total_amount,
                'notes'                 => $order->notes ?? '-',
                'delivery_scheduled_at' => $order->delivery_scheduled_at?->format('d/m/Y H:i') ?? 'Sekarang',
                'completed_target_at'   => $order->completed_target_at?->toIso8601String(), // FIX: Global Timer for Courier Detail
                'items' => $order->products->map(fn($p) => [
                    'name'     => $p->name,
                    'quantity' => (int) $p->pivot->quantity,
                    'price'    => (int) $p->pivot->price_at_sale,
                    'subtotal' => (int) $p->pivot->subtotal,
                ]),

                // BUKTI FOTO KALAU SUDAH SELESAI
                'delivery_proof' => $order->deliveryProof ? [
                    'image_url'   => Str::startsWith($order->deliveryProof->image_url, ['http://', 'https://']) 
                        ? $order->deliveryProof->image_url 
                        : asset($order->deliveryProof->image_url),
                    'notes'       => $order->deliveryProof->notes ?? 'Tidak ada catatan',
                    'uploaded_by' => $order->deliveryProof->uploadedBy?->name ?? 'Kurir',
                    'uploaded_at' => $order->deliveryProof->created_at->format('d M Y H:i'),
                ] : null,
            ]
        ]);
    }
    // ==================== AMBIL PESANAN ====================
    public function pickup($order_number, Request $request)
    {
        $kurir = $request->user();

        $order = Order::where('order_number', $order_number)
            ->where('status', 'READY')
            ->firstOrFail();

        $order->update([
            'status'        => 'ON_DELIVERY',
            'courier_id'    => $kurir->id,
            'delivery_time' => now(), // OTOMATIS!
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pesanan berhasil diambil dan sedang diantar!',
        ]);
    }

    // ==================== SELESAI ANTAR ====================
  // ==================== SELESAI ANTAR (FIXED — NO SYMLINK NEEDED!) ====================
public function complete($order_number, Request $request)
{
    // VALIDASI FOTO: WAJIB, maks 5MB, format JPG/PNG
    $request->validate([
        'image' => [
            'required',
            'image',
            'mimes:jpeg,jpg,png',
            'max:5120', // 5MB
            'dimensions:max_width=4096,max_height=4096',
        ],
        'notes' => 'nullable|string|max:500',
    ]);

    $kurir = $request->user();

    // Ambil order yang sedang diantar oleh kurir ini
    $order = Order::where('order_number', $order_number)
        ->where('courier_id', $kurir->id)
        ->where('status', 'ON_DELIVERY')
        ->firstOrFail();

    // Anti double upload
    if ($order->deliveryProof()->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Bukti pengantaran sudah pernah diupload!'
        ], 400);
    }

    return DB::transaction(function () use ($order, $request, $kurir) {
        $image = $request->file('image');

        // Detect public_html for Shared Hosting
        // Current: .../public_html/depot/public
        // Target: .../public_html/delivery_proof
        $basePublicPath = public_path();
        $targetFolder = $basePublicPath . '/delivery_proof'; // Default: depot/public/delivery_proof
        
        // Cek apakah kita ada di dalam subfolder (misal: depot/public) dan ingin simpan di root public_html
        if (str_contains($basePublicPath, 'depot\public') || str_contains($basePublicPath, 'depot/public')) {
            // Naik 2 level: depot/public -> depot -> public_html
            $candidatePath = dirname($basePublicPath, 2) . '/delivery_proof';
            if (is_dir(dirname($candidatePath))) { // Pastikan parent (public_html) ada
                $targetFolder = $candidatePath;
            }
        }

        if (!file_exists($targetFolder)) {
            mkdir($targetFolder, 0755, true);
        }

        // Nama file unik
        $filename = $order->order_number . '_' . time() . '.' . $image->getClientOriginalExtension();

        // SIMPAN KE FOLDER TUJUAN
        $image->move($targetFolder, $filename);

        // URL YANG BENAR & PASTI JALAN
        $url = '/delivery_proof/' . $filename;
        $fullUrl = asset($url); // https://hydroexpert.my.id/delivery_proof/...

        // Simpan bukti
        $order->deliveryProof()->create([
            'uploaded_by' => $kurir->id,
            'image_url'   => $fullUrl,  // LANGSUNG FULL URL!
            'notes'       => $request->notes ?? null,
        ]);

        // Update status jadi COMPLETE
        $order->update([
            'status'         => 'COMPLETE',
            'completed_time' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'message'    => 'Pesanan selesai diantar! Bukti foto tersimpan.',
            'image_url'  => $fullUrl,  // LANGSUNG BISA DIPAKE DI FLUTTER!
        ]);
    });
}
}