<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Order;

class OwnerController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            return redirect()->route('owner.dashboard');
        }
        return view('owner.login');
    }

    public function doLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            return redirect()->route('owner.dashboard');
        }

        return back()->withErrors(['email' => 'Email atau password salah']);
    }

    public function dashboard()
    {
        $orders = Order::with('customer')
            ->latest()
            ->paginate(20);

        $incompleteOrders = Order::with(['customer', 'products'])
            ->whereNotIn('status', ['COMPLETE', 'CANCELLED'])
            ->latest()
            ->get();

        $incompleteCount = $incompleteOrders->count();

        return view('owner.dashboard', compact('orders', 'incompleteOrders', 'incompleteCount'));
    }

    public function logout()
    {
        Auth::logout();
        return redirect()->route('owner.login')->with('success', 'Logout berhasil');
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:DRAFT,PREPARED,READY,ON_DELIVERY,COMPLETE,CANCELLED'
        ]);

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status diubah jadi ' . $request->status,
            'new_status' => $request->status
        ]);
    }

    /**
     * Mengambil daftar semua pesanan yang belum selesai dalam format JSON
     */
    public function getIncompleteOrders()
    {
        $incompleteOrders = Order::with(['customer', 'products'])
            ->whereNotIn('status', ['COMPLETE', 'CANCELLED'])
            ->latest()
            ->get()
            ->map(function ($order) {
                $statusVal = is_object($order->status) ? ($order->status->value ?? (string)$order->status) : (string)$order->status;
                $phone = $order->customer->phone_number ?? '';
                $cleanPhone = $this->formatWaNumber($phone);

                return [
                    'id'              => $order->id,
                    'order_number'    => $order->order_number,
                    'status'          => $statusVal,
                    'status_label'    => $this->getStatusLabel($statusVal),
                    'order_type'      => is_object($order->order_type) ? ($order->order_type->value ?? (string)$order->order_type) : (string)$order->order_type,
                    'payment_type'    => is_object($order->payment_type) ? ($order->payment_type->value ?? (string)$order->payment_type) : (string)$order->payment_type,
                    'customer_name'   => $order->customer->name ?? 'Pelanggan',
                    'customer_phone'  => $phone,
                    'wa_number'       => $cleanPhone,
                    'total_amount'    => (int) $order->total_amount,
                    'total_formatted' => 'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'created_at'      => $order->created_at?->format('d/m/Y H:i') ?? '-',
                    'items_summary'   => $order->products->map(fn($p) => ($p->pivot->product_name ?? $p->name) . ' (' . ($p->pivot->quantity ?? 1) . ')')->join(', '),
                ];
            });

        return response()->json([
            'success' => true,
            'count'   => $incompleteOrders->count(),
            'orders'  => $incompleteOrders,
        ]);
    }

    /**
     * Memproses blast WhatsApp untuk pesanan yang dipilih
     */
    public function blastOrders(Request $request)
    {
        $request->validate([
            'order_ids'     => 'required|array|min:1',
            'order_ids.*'   => 'exists:orders,id',
            'template'      => 'required|string',
            'gateway_token' => 'nullable|string',
        ]);

        $token = $request->gateway_token ?: env('FONNTE_TOKEN') ?: env('WA_TOKEN');
        $orders = Order::with(['customer', 'products'])->whereIn('id', $request->order_ids)->get();

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($orders as $order) {
            $phone = $this->formatWaNumber($order->customer->phone_number ?? '');
            if (!$phone) {
                $results[] = [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'customer'     => $order->customer->name ?? 'Pelanggan',
                    'phone'        => '-',
                    'success'      => false,
                    'message'      => 'Nomor telepon tidak valid / kosong',
                ];
                $failedCount++;
                continue;
            }

            $statusVal = is_object($order->status) ? ($order->status->value ?? (string)$order->status) : (string)$order->status;
            $statusText = $this->getStatusLabel($statusVal);

            $message = str_replace(
                ['{nama}', '{nomor_order}', '{status}', '{total}', '{depot}'],
                [
                    $order->customer->name ?? 'Pelanggan',
                    $order->order_number,
                    $statusText,
                    'Rp ' . number_format($order->total_amount, 0, ',', '.'),
                    'HydroExpert'
                ],
                $request->template
            );

            if ($token) {
                try {
                    $response = Http::withHeaders([
                        'Authorization' => $token,
                    ])->timeout(12)->post('https://api.fonnte.com/send', [
                        'target'  => $phone,
                        'message' => $message,
                    ]);

                    $resJson = $response->json();
                    if ($response->successful() && ($resJson['status'] ?? true)) {
                        $results[] = [
                            'order_id'     => $order->id,
                            'order_number' => $order->order_number,
                            'customer'     => $order->customer->name ?? 'Pelanggan',
                            'phone'        => $phone,
                            'success'      => true,
                            'message'      => 'Berhasil terkirim via Gateway',
                        ];
                        $successCount++;
                    } else {
                        $errMsg = $resJson['reason'] ?? $resJson['message'] ?? 'Gagal dari Gateway WA';
                        $results[] = [
                            'order_id'     => $order->id,
                            'order_number' => $order->order_number,
                            'customer'     => $order->customer->name ?? 'Pelanggan',
                            'phone'        => $phone,
                            'success'      => false,
                            'message'      => $errMsg,
                        ];
                        $failedCount++;
                    }
                } catch (\Exception $e) {
                    $results[] = [
                        'order_id'     => $order->id,
                        'order_number' => $order->order_number,
                        'customer'     => $order->customer->name ?? 'Pelanggan',
                        'phone'        => $phone,
                        'success'      => false,
                        'message'      => 'Koneksi error: ' . $e->getMessage(),
                    ];
                    $failedCount++;
                }
            } else {
                // Mode tanpa gateway API: Siapkan link direct wa.me
                $waUrl = 'https://api.whatsapp.com/send?phone=' . $phone . '&text=' . rawurlencode($message);
                $results[] = [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'customer'     => $order->customer->name ?? 'Pelanggan',
                    'phone'        => $phone,
                    'success'      => true,
                    'wa_url'       => $waUrl,
                    'message'      => 'Siap dibuka di WhatsApp',
                ];
                $successCount++;
            }
        }

        return response()->json([
            'success'       => true,
            'has_gateway'   => !empty($token),
            'success_count' => $successCount,
            'failed_count'  => $failedCount,
            'results'       => $results,
        ]);
    }

    /**
     * Format nomor HP agar standar format internasional Indonesia (62xxx)
     */
    protected function formatWaNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($phone)) return '';
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }
        return $phone;
    }

    /**
     * Dapatkan label human-readable untuk status pesanan
     */
    protected function getStatusLabel($status)
    {
        return match($status) {
            'DRAFT'       => 'Draft / Baru Masuk',
            'PREPARED'    => 'Sedang Disiapkan Staff',
            'READY'       => 'Siap (Menunggu Kurir / Diambil)',
            'ON_DELIVERY' => 'Sedang Dalam Pengantaran',
            'COMPLETE'    => 'Selesai',
            'CANCELLED'   => 'Dibatalkan',
            default       => (string)$status,
        };
    }
}