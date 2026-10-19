<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'       => 'required|email',
            'password'    => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Izinkan login untuk admin, kasir, dan kurir
        if (!in_array($user->role, ['admin', 'kasir', 'kurir', 'owner'])) {
            throw ValidationException::withMessages([
                'email' => ['Akun ini tidak diizinkan login ke aplikasi ini.'],
            ]);
        }

        // GEMBOK KETAT: Jika kasir login saat kasir sebelumnya belum tutup shift, tolak login!
        if ($user->role === 'kasir') {
            $unclosedShift = \App\Models\CashierShift::where('status', 'OPEN')
                ->where('user_id', '!=', $user->id)
                ->with('user:id,name')
                ->latest('id')
                ->first();

            if ($unclosedShift) {
                $otherKasirName = $unclosedShift->user?->name ?? 'Kasir sebelumnya';
                $openedAt = $unclosedShift->start_time->format('d/m/Y H:i');
                return response()->json([
                    'success' => false,
                    'code'    => 'UNCLOSED_SHIFT_EXISTS',
                    'message' => "Akses Masuk Ditolak!\n\nKasir {$otherKasirName} belum melakukan Tutup Shift (buka sejak {$openedAt}).\n\nMinta {$otherKasirName} untuk login dan menutup shiftnya terlebih dahulu sebelum kasir lain bisa mulai.",
                ], 403);
            }
        }

        $deviceName = $request->input('device_name') ?: 'suitest-client';

        // Hapus token lama dari device yang sama (opsional, biar ga numpuk)
        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role, // ini penting banget buat Flutter bedain kasir/kurir
            ],
            'token'   => $token,
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user'    => $request->user()->only(['id', 'name', 'email', 'role']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil logout',
        ]);
    }
}