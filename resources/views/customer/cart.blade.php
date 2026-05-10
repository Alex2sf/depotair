@extends('layouts.app')

@section('content')
<!-- Header (Web style, can keep or remove for mobile feel) -->
<div class="bg-white px-4 py-3 shadow-sm sticky top-[56px] z-40 mb-2 border-b border-gray-100 flex items-center justify-between">
    <div class="flex items-center gap-2">
         <h1 class="font-bold text-gray-800 text-lg">Keranjang</h1>
         <span class="text-xs text-shopee bg-orange-50 px-2 py-0.5 rounded-full border border-orange-100 font-bold">
            {{ collect($cart)->sum('qty') }}
         </span>
    </div>
    <a href="/" class="text-xs text-shopee font-bold">Tambah Lagi</a>
</div>

<div class="pb-32 px-4 space-y-4 min-h-screen">
    @forelse($cart as $id => $item)
    <div class="bg-white p-3 rounded-xl shadow-sm border border-gray-100 flex gap-3 relative group">
        <!-- Delete Button (Top Right Absolute) -->
        <form action="{{ route('cart.remove', $id) }}" method="POST" class="absolute top-2 right-2 z-10">
            @csrf
            <button type="submit" class="text-gray-300 hover:text-red-500 p-1">
                <i class="fas fa-trash-alt text-sm"></i>
            </button>
        </form>

        <!-- Image -->
        <div class="w-20 h-20 bg-gray-50 rounded-lg overflow-hidden flex-shrink-0 border border-gray-100">
             @if($item['image'])
                <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
             @else
                <div class="w-full h-full flex items-center justify-center text-gray-300">
                    <i class="fas fa-image"></i>
                </div>
             @endif
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col justify-between py-1">
            <div>
                <h3 class="font-bold text-sm text-gray-800 line-clamp-1 pr-6">{{ $item['name'] }}</h3>
                <p class="text-[10px] text-gray-500">Stok Tersedia</p>
            </div>
            
            <div class="flex items-end justify-between mt-2">
                <div class="text-shopee font-bold text-sm">Rp {{ number_format($item['price'], 0, ',', '.') }}</div>
                
                <!-- Qty Control -->
                <form action="{{ route('cart.update') }}" method="POST" class="flex items-center bg-gray-50 rounded-lg border border-gray-200">
                    @csrf
                    <!-- Hidden fields for other items to persist cart state if needed, 
                         but simplified here to update one by one or standard implementation -->
                    <!-- Best practice for granular update: single item update or JS. 
                         Laravel default controller expects array. We simulate array structure. -->
                    
                    <!-- We'll use a little JS trick or specific layout for simple HTML form submission 
                         Ideally each button submits. -->
                    
                    <!-- Decrement -->
                    <button type="submit" name="qty[{{ $id }}]" value="{{ $item['qty'] - 1 }}" class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-200 rounded-l-lg transition {{ $item['qty'] <= 1 ? 'pointer-events-none opacity-50' : '' }}">
                        <i class="fas fa-minus text-[10px]"></i>
                    </button>
                    
                    <input type="text" readonly value="{{ $item['qty'] }}" class="w-8 text-center text-xs font-bold bg-transparent text-gray-700">
                    
                    <!-- Increment -->
                    <button type="submit" name="qty[{{ $id }}]" value="{{ $item['qty'] + 1 }}" class="w-7 h-7 flex items-center justify-center text-gray-500 hover:bg-gray-200 rounded-r-lg transition">
                        <i class="fas fa-plus text-[10px]"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @empty
    <div class="flex flex-col items-center justify-center py-20 text-gray-400">
        <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <i class="fas fa-shopping-cart text-3xl opacity-50"></i>
        </div>
        <p class="text-sm font-medium">Keranjang masih kosong</p>
        <a href="/" class="mt-4 px-6 py-2 bg-shopee text-white text-xs font-bold rounded-full shadow-md hover:shadow-lg transition">
            Belanja Sekarang
        </a>
    </div>
    @endforelse
</div>

<!-- FIXED BOTTOM BAR Checkbout -->
@if(!empty($cart))
<div class="fixed bottom-[64px] left-0 right-0 bg-white border-t border-gray-100 p-4 z-[60] max-w-md mx-auto shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
    <div class="flex items-center justify-between">
        <div class="flex flex-col">
            <span class="text-[10px] text-gray-500">Total Pembayaran</span>
            <span class="text-lg font-bold text-shopee">Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
        </div>
        <a href="{{ route('checkout') }}" class="bg-shopee text-white font-bold py-3 px-8 rounded-lg shadow-md hover:bg-red-600 transition flex items-center gap-2">
            <span>Checkout</span>
            <span class="bg-white/20 text-[10px] px-1.5 rounded">{{ collect($cart)->sum('qty') }}</span>
        </a>
    </div>
</div>
@endif

@endsection