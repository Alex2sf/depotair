<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            // SEMUA ROLE BOLEH MASUK — GA PERLU CEK LAGI
            return redirect()->route('owner.dashboard');
        }

        return back()->withErrors(['email' => 'Email atau password salah']);
    }

    public function dashboard()
    {
        $orders = Order::with('customer')
            ->latest()
            ->paginate(20);

        return view('owner.dashboard', compact('orders'));
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
}