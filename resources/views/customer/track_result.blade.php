@extends('layouts.app')

@section('content')
<!-- Custom Back Header inside Mobile Layout -->
<div class="bg-white px-4 py-3 shadow-sm flex items-center gap-4 sticky top-[56px] z-40">
    <a href="{{ route('track.form') }}" class="text-gray-600">
        <i class="fas fa-arrow-left"></i>
    </a>
    <div class="flex-1">
        <h1 class="font-bold text-gray-800 text-lg">Rincian Pesanan</h1>
        <div class="flex items-center gap-2 mt-1">
            <p class="text-xs text-gray-500 font-mono">#{{ $order->order_number }}</p>
            <button onclick="copyToClipboard('{{ $order->order_number }}')" class="text-shopee bg-orange-50 hover:bg-orange-100 p-1.5 rounded-md transition duration-200" title="Salin No. Pesanan">
                <i class="far fa-copy text-xs"></i> <span class="text-[10px] font-bold ml-1">Salin</span>
            </button>
        </div>
    </div>
    <span class="text-shopee text-xs font-bold">{{ $order->status }}</span>
</div>

<script>
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            // Simple alert or custom toast
            alert('Nomor Pesanan berhasil disalin: ' + text);
        }).catch(err => {
            console.error('Gagal menyalin: ', err);
            prompt("Salin manual:", text); // Fallback
        });
    } else {
        // Fallback for older browsers
        prompt("Salin manual:", text);
    }
}
</script>

<div class="p-4 pb-24 space-y-4">

    <!-- STATUS TIMELINE (Simplified) -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 text-sm mb-4">Status Pengiriman</h3>
        
        @php
            $statuses = ['DRAFT', 'PREPARED', 'READY', 'ON_DELIVERY', 'COMPLETE'];
            $currentIdx = array_search($order->status->value, $statuses);
            if ($order->status->value === 'CANCELLED') $currentIdx = -1; 
        @endphp

        <div class="relative space-y-6 pl-2">
            <!-- Line -->
            <div class="absolute top-2 bottom-2 left-[7px] w-[2px] bg-gray-200"></div>

            <!-- Steps -->
            @foreach($statuses as $idx => $step)
                @php 
                    $active = ($idx <= $currentIdx);
                    $isCurrent = ($idx === $currentIdx);
                    
                    $labels = [
                        'DRAFT' => 'Pesanan Dibuat',
                        'PREPARED' => 'Sedang Disiapkan',
                        'READY' => 'Siap Diantar/Ambil',
                        'ON_DELIVERY' => 'Kurir Menuju Lokasi',
                        'COMPLETE' => 'Pesanan Selesai'
                    ];
                @endphp
                <div class="relative flex items-center gap-4">
                     <div class="z-10 w-4 h-4 rounded-full border-2 {{ $active ? 'bg-shopee border-shopee' : 'bg-white border-gray-300' }}"></div>
                     <div>
                         <p class="text-xs font-bold {{ $active ? 'text-gray-800' : 'text-gray-400' }}">{{ $labels[$step] }}</p>
                         @if($isCurrent)
                            <p class="text-[10px] text-shopee">Status Saat Ini</p>
                         @endif
                     </div>
                </div>
            @endforeach
        </div>

         @if($order->status->value === 'CANCELLED')
            <div class="mt-4 bg-red-50 text-red-600 p-3 rounded-lg text-xs font-bold text-center">
                Pesanan Dibatalkan
            </div>
         @endif
    </div>

    <!-- ADDRESS INFO -->
    @if($order->order_type == 'DELIVERY')
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 text-sm mb-2"><i class="fas fa-map-marker-alt text-shopee mr-1"></i> Alamat Pengiriman</h3>
        <p class="text-xs text-gray-600 leading-relaxed">{{ $order->delivery_address }}</p>
        
        @if($order->delivery_scheduled_at)
            <div class="mt-2 text-xs text-blue-600 font-medium">
                <i class="far fa-clock mr-1"></i> Jadwal: {{ \Carbon\Carbon::parse($order->delivery_scheduled_at)->format('d M Y, H:i') }}
            </div>
        @endif
        
        @if($order->address_link)
            <a href="{{ $order->address_link }}" target="_blank" class="mt-3 block text-center text-xs font-bold text-shopee border border-shopee rounded py-1.5 hover:bg-orange-50">
                Lihat di Peta
            </a>
        @endif
    </div>
    @endif

    <!-- PRODUCT LIST -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <h3 class="font-bold text-gray-800 text-sm mb-3">Rincian Produk</h3>
        <div class="space-y-3">
             @foreach($order->products as $item)
             <div class="flex gap-3">
                  <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                       <i class="fas fa-box text-gray-400"></i>
                  </div>
                  <div class="flex-1">
                       <h4 class="text-xs font-bold text-gray-800 line-clamp-1">{{ $item->pivot->product_name }}</h4>
                       <div class="flex justify-between items-end mt-1">
                            <span class="text-xs text-gray-500">{{ $item->pivot->quantity }} x Rp {{ number_format($item->pivot->price_at_sale, 0, ',', '.') }}</span>
                            <span class="text-xs font-bold text-gray-800">Rp {{ number_format($item->pivot->subtotal, 0, ',', '.') }}</span>
                       </div>
                  </div>
             </div>
             @endforeach
        </div>
        
        <!-- Summary -->
        <div class="border-t border-dashed border-gray-200 mt-4 pt-4 space-y-1">
             <div class="flex justify-between text-xs text-gray-500">
                 <span>Subtotal Produk</span>
                 <span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span>
             </div>
             <div class="flex justify-between text-sm font-bold text-gray-800 mt-2">
                 <span>Total Pembayaran</span>
                 <span class="text-shopee">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</span>
             </div>
        </div>
    </div>

    <!-- CONTACT BUTTON -->
    <a href="https://wa.me/6287727777302?text=Halo%20Admin%2C%20mau%20tanya%20pesanan%20{{ $order->order_number }}" target="_blank" class="block w-full text-center bg-green-500 text-white font-bold py-3 rounded-lg shadow-md hover:bg-green-600 transition">
        <i class="fab fa-whatsapp mr-2"></i> Hubungi Penjual
    </a>

</div>
@endsection