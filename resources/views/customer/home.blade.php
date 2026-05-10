{{-- resources/views/customer/home.blade.php --}}
@extends('layouts.app')

@section('content')

<!-- SUCCESS POPUP MODAL (Triggered by Session) -->
@if(session('success_order'))
<div class="fixed inset-0 z-[100] flex items-center justify-center px-4 bg-black/60 backdrop-blur-sm animate-fade-in">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm text-center transform scale-100 animate-bounce-in relative">
        <!-- Close Button -->
        <button onclick="this.closest('.fixed').remove()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600">
            <i class="fas fa-times text-xl"></i>
        </button>

        <!-- Icon -->
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-500 animate-pulse">
            <i class="fas fa-check-circle text-5xl"></i>
        </div>
        
        <h2 class="text-2xl font-bold text-gray-800 mb-1">Pesanan Berhasil!</h2>
        <p class="text-gray-500 text-sm mb-6">Terima kasih, pesananmu sedang diproses.</p>

        <!-- Order ID Box -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 mb-6">
            <p class="text-xs text-gray-500 uppercase font-bold tracking-wider">Nomor Pesanan</p>
            <div class="flex items-center justify-center gap-2 mt-1">
                <p class="text-xl font-mono font-bold text-gray-800 tracking-widest select-all">
                    #{{ session('success_order')->order_number }}
                </p>
                <button onclick="copyToClipboard('{{ session('success_order')->order_number }}')" class="p-2 bg-white rounded-full shadow-sm border border-gray-200 text-gray-500 hover:text-shopee hover:border-shopee transition-all active:scale-95" title="Salin">
                    <i class="far fa-copy"></i>
                </button>
            </div>
        </div>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            alert('Nomor Pesanan berhasil disalin: ' + text);
        }).catch(err => {
            console.error('Gagal menyalin: ', err);
            prompt("Salin manual:", text); 
        });
    } else {
        prompt("Salin manual:", text);
    }
}
</script>

        <!-- Buttons -->
        <div class="space-y-3">
            <a href="{{ route('track.form') }}?order_number={{ session('success_order')->order_number }}" class="block w-full bg-shopee text-white font-bold py-3 rounded-lg shadow hover:bg-red-600 transition">
                Lacak Pesanan
            </a>
            <button onclick="this.closest('.fixed').remove()" class="block w-full bg-white text-gray-600 border border-gray-300 font-bold py-3 rounded-lg hover:bg-gray-50 transition">
                Belanja Lagi
            </button>
        </div>
    </div>
</div>
@endif

<!-- STORE HEADER BANNER -->
<div class="bg-white p-4 pb-0 mb-2">
    <div class="rounded-xl overflow-hidden shadow-sm relative h-48 w-full">
         <img src="https://images.unsplash.com/photo-1548839140-29a749e1cf4d?q=80&w=800&auto=format&fit=crop" class="w-full h-full object-cover" alt="Banner">
         <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-4">
             <div>
                 <h1 class="text-white font-bold text-xl drop-shadow-md">Selamat Datang di Depot Online</h1>
                 <p class="text-white/80 text-sm drop-shadow-md">Segar, Bersih, dan Terpercaya</p>
             </div>
         </div>
    </div>
</div>

<!-- SEARCH BAR (Functional & Sticky) -->
<div class="px-4 -mt-6 mb-4 relative z-30 sticky top-[72px]">
    <form action="{{ route('customer.home') }}" method="GET">
        <div class="bg-white rounded-lg shadow-lg flex items-center p-2 border border-gray-100 ring-1 ring-black/5">
            <i class="fas fa-search text-gray-400 ml-2"></i>
            <input type="text" name="search" value="{{ request('search') }}" 
                   class="w-full px-3 py-1 outline-none text-sm text-gray-700 placeholder-gray-400 bg-transparent" 
                   placeholder="Cari air galon, tisu, dll...">
            @if(request('search'))
                <a href="{{ route('customer.home') }}" class="text-gray-400 hover:text-red-500 mr-2">
                    <i class="fas fa-times-circle"></i>
                </a>
            @endif
        </div>
        <!-- Keep current filter if exists -->
        @if(request('type'))
            <input type="hidden" name="type" value="{{ request('type') }}">
        @endif
    </form>
</div>

<!-- FUNCTIONAL CATEGORY FILTERS -->
<div class="bg-white p-4 pt-2">
    <div class="grid grid-cols-4 gap-4">
        <!-- ALL -->
        <a href="{{ route('customer.home', ['type' => 'ALL']) }}" class="flex flex-col items-center gap-2 group">
            <div class="w-12 h-12 rounded-full {{ request('type') == 'ALL' || !request('type') ? 'bg-shopee text-white' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center transition shadow-sm border border-gray-100">
                <i class="fas fa-th-large text-lg"></i>
            </div>
            <span class="text-[10px] {{ request('type') == 'ALL' || !request('type') ? 'font-bold text-shopee' : 'text-gray-600' }}">Semua</span>
        </a>

        <!-- REFILL -->
        <a href="{{ route('customer.home', ['type' => 'REFILL']) }}" class="flex flex-col items-center gap-2 group">
            <div class="w-12 h-12 rounded-full {{ request('type') == 'REFILL' ? 'bg-blue-600 text-white' : 'bg-blue-50 text-blue-600' }} flex items-center justify-center transition shadow-sm border border-blue-100">
                <i class="fas fa-tint text-lg"></i>
            </div>
            <span class="text-[10px] {{ request('type') == 'REFILL' ? 'font-bold text-blue-600' : 'text-gray-600' }}">Isi Ulang</span>
        </a>

        <!-- NEW UNIT -->
        <a href="{{ route('customer.home', ['type' => 'NEW_UNIT']) }}" class="flex flex-col items-center gap-2 group">
             <div class="w-12 h-12 rounded-full {{ request('type') == 'NEW_UNIT' ? 'bg-teal-600 text-white' : 'bg-teal-50 text-teal-600' }} flex items-center justify-center transition shadow-sm border border-teal-100">
                <i class="fas fa-bottle-water text-lg"></i>
            </div>
            <span class="text-[10px] {{ request('type') == 'NEW_UNIT' ? 'font-bold text-teal-600' : 'text-gray-600' }}">Galon Baru</span>
        </a>

        <!-- CONSUMABLE -->
        <a href="{{ route('customer.home', ['type' => 'CONSUMABLE']) }}" class="flex flex-col items-center gap-2 group">
             <div class="w-12 h-12 rounded-full {{ request('type') == 'CONSUMABLE' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-600' }} flex items-center justify-center transition shadow-sm border border-orange-100">
                <i class="fas fa-box text-lg"></i>
            </div>
            <span class="text-[10px] {{ request('type') == 'CONSUMABLE' ? 'font-bold text-orange-600' : 'text-gray-600' }}">Barang</span>
        </a>
    </div>
</div>

<!-- PRODUCT HEADER -->
<div class="px-4 py-2 mt-2 flex items-center justify-between">
    <h3 class="font-bold text-gray-800 text-lg border-l-4 border-shopee pl-2">
        @if(request('type') == 'REFILL') Isi Ulang Galon
        @elseif(request('type') == 'NEW_UNIT') Paket Galon Baru
        @elseif(request('type') == 'CONSUMABLE') Barang Konsumsi
        @else Semua Produk
        @endif
    </h3>
    <span class="text-xs text-shopee bg-orange-50 px-2 py-1 rounded-full border border-orange-100 font-medium">{{ $products->count() }} Produk</span>
</div>

<!-- PRODUCT GRID -->
<div class="p-4 pt-0 grid grid-cols-2 gap-4 pb-20">
    @forelse($products as $p)
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition duration-200 overflow-hidden border border-gray-100 flex flex-col h-full">
        <!-- Image with subtle zoom effect on hover -->
        <div class="relative w-full pt-[100%] bg-gray-50 overflow-hidden group"> 
            <img src="{{ $p->image_url }}" alt="{{ $p->name }}" class="absolute top-0 left-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
            
             @if(!$p->is_enabled)
                <div class="absolute inset-0 bg-black/50 flex items-center justify-center backdrop-blur-[2px]">
                    <span class="text-white font-bold text-xs bg-red-600 px-3 py-1 rounded-full shadow-lg">HABIS</span>
                </div>
            @endif
        </div>

        <!-- Product Info -->
        <div class="p-3 flex flex-col flex-1">
            <h3 class="font-bold text-gray-800 text-sm mb-1 line-clamp-2 min-h-[40px]">{{ $p->name }}</h3>
            
            <div class="flex items-center justify-between mb-2">
                 <span class="text-xs font-bold text-shopee">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                 <span class="text-[10px] text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                    Stok: {{ $p->inventory->quantity ?? 0 }} {{ $p->unit }}
                 </span>
            </div>

            @if(($p->inventory->quantity ?? 0) > 0)
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                    <button type="submit" class="w-full bg-shopee text-white text-xs font-bold py-2 rounded-lg hover:bg-red-600 transition flex items-center justify-center gap-1 active:scale-95">
                        <i class="fas fa-plus"></i> Keranjang
                    </button>
                </form>
            @else
                <button disabled class="w-full bg-gray-300 text-gray-500 text-xs font-bold py-2 rounded-lg cursor-not-allowed">
                    Stok Habis
                </button>
            @endif
        </div>
    </div>
    @empty
    <div class="col-span-2 py-10 flex flex-col items-center justify-center text-gray-400">
        <i class="fas fa-box-open text-4xl mb-3"></i>
        <p class="text-sm">Belum ada produk untuk kategori ini.</p>
        <a href="{{ route('customer.home') }}" class="mt-4 text-shopee text-xs font-bold border border-shopee px-4 py-2 rounded-full">Lihat Semua</a>
    </div>
    @endforelse
</div>
@endsection