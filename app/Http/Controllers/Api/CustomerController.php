<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CustomerSearchOrCreateRequest;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function searchOrCreate(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string',
        ]);

        $name = trim($request->name);
        $phone = $request->phone_number ? trim($request->phone_number) : null;

        // 1. SKENARIO "CUMA CEK DOANG" (Phone Kosong)
        if (empty($phone)) {
            // Cari pelanggan lama berdasarkan nama
            $existing = Customer::where('name', $name)->first();

            if ($existing) {
                // FITUR BARU: Update alamat meskipun nomor HP kosong (karena ini pelanggan lama)
                if ($request->filled('address') && $request->address !== '-' && $request->address !== $existing->address) {
                    $existing->update(['address' => $request->address]);
                    $existing->refresh(); // Ambil data terbaru
                }

                return response()->json([
                    'success' => true,
                    'customer' => $existing,
                    'is_existing' => true,
                    'message' => 'Pelanggan lama ditemukan & alamat diperbarui.'
                ]);
            } else {
                // Gagal: Karena dianggap pelanggan baru, wajib isi No HP
                return response()->json([
                    'success' => false,
                    'message' => 'Pelanggan baru? Wajib isi Nomor HP!'
                ], 422); 
            }
        }

        // 2. SKENARIO "SIMPAN/UPDATE DATA" (Ada Nama & Phone)
        $customer = Customer::updateOrCreate(
            ['name' => $name], 
            [
                'phone_number' => $phone,
                'address' => $request->address ?? '-', 
            ]
        );

        return response()->json([
            'success' => true,
            'customer' => $customer,
            'message' => 'Data pelanggan berhasil disimpan!'
        ]);
    }
}