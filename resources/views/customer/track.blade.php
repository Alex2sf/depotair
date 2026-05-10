@extends('layouts.app')

@section('content')
<div class="p-4">
    <!-- Header simple -->
    <div class="text-center mt-6 mb-8">
        <h1 class="text-2xl font-bold text-gray-800">Lacak Pesanan</h1>
        <p class="text-xs text-gray-500 mt-1">Masukkan Nomor Pesanan untuk melacak</p>
    </div>

    <!-- Card Input -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <form action="{{ route('track.order') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor Pesanan</label>
                <div class="relative">
                    <input type="text" name="order_number" 
                        class="w-full border-b-2 border-gray-300 focus:border-shopee py-2 text-xl font-bold text-gray-800 bg-transparent outline-none placeholder-gray-300 uppercase font-mono transition-colors"
                        placeholder="ORD..." required>
                    <i class="fas fa-barcode absolute right-0 top-3 text-gray-400"></i>
                </div>
                @error('order_number')
                    <p class="text-red-500 text-xs mt-2 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full bg-shopee text-white font-bold py-3 rounded-lg shadow-md active:bg-red-600 transition transform active:scale-95">
                Cek Pesanan
            </button>
        </form>
    </div>

    <!-- History Login/Promo Placeholder -->
    <div class="mt-8 text-center text-gray-400">
        <i class="fas fa-truck-fast text-4xl mb-2 opacity-20"></i>
        <p class="text-xs">Kami pastikan air anda sampai dengan aman.</p>
    </div>
</div>
@endsection