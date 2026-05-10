@extends('layouts.app')

@section('content')
<!-- Header Mobile Style -->
<div class="bg-white px-4 py-3 shadow-sm sticky top-[56px] z-40 flex items-center gap-4">
    <a href="{{ route('cart.show') }}" class="text-gray-600">
        <i class="fas fa-arrow-left"></i>
    </a>
    <h1 class="font-bold text-gray-800 text-lg">Checkout</h1>
</div>

<form action="{{ route('order.submit') }}" method="POST" id="checkoutForm" class="pb-32 container mx-auto max-w-md">
    @csrf
    
    <!-- ALAMAT & PENGIRIMAN -->
    <div class="bg-white mt-2 p-4 shadow-sm border-y border-gray-100">
         <h2 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-map-marker-alt text-shopee"></i> Alamat Pengiriman
         </h2>
         
         <!-- Toggle Type -->
         <div class="flex bg-gray-100 p-1 rounded-lg mb-4">
             <label class="flex-1 text-center cursor-pointer">
                 <input type="radio" name="order_type" value="DELIVERY" checked class="peer hidden" onchange="toggleAddress(this.value)">
                 <div class="py-2 text-xs font-bold text-gray-500 rounded-md peer-checked:bg-white peer-checked:text-shopee peer-checked:shadow-sm transition">
                    Delivery
                 </div>
             </label>
             <label class="flex-1 text-center cursor-pointer">
                 <input type="radio" name="order_type" value="SELF_PICKUP" class="peer hidden" onchange="toggleAddress(this.value)">
                 <div class="py-2 text-xs font-bold text-gray-500 rounded-md peer-checked:bg-white peer-checked:text-green-600 peer-checked:shadow-sm transition">
                    Ambil Sendiri
                 </div>
             </label>
         </div>

         <!-- Input Fields -->
         <div class="space-y-3">
             <div>
                <input type="text" name="name" placeholder="Nama Lengkap" required 
                    class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent"
                    autocomplete="name">
             </div>
             <div>
                <input type="tel" name="phone" placeholder="Nomor WhatsApp" required 
                    class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent"
                    autocomplete="tel">
             </div>
             
             <div id="address_section">
                <textarea name="address" id="address_input" placeholder="Alamat Lengkap (Jalan, No Rumah, Patokan)" rows="2" required
                    class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent resize-none"></textarea>
                
                <input type="url" name="address_link" placeholder="Link Google Maps (Opsional)" 
                    class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent">
             </div>
             
             <!-- Scheduled Delivery -->
            <div id="schedule_section">
                <label class="text-[10px] text-gray-500 font-bold uppercase mt-2 block">Jadwal Pengiriman (Opsional)</label>
                <input type="datetime-local" name="delivery_scheduled_at" 
                    class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent text-gray-600">
            </div>
         </div>
    </div>

    <!-- METODE PEMBAYARAN -->
    <div class="bg-white mt-2 p-4 shadow-sm border-y border-gray-100">
        <h2 class="text-sm font-bold text-gray-800 mb-3 flex items-center gap-2">
            <i class="fas fa-wallet text-blue-600"></i> Pembayaran
         </h2>
         <div class="grid grid-cols-4 gap-2">
             <label class="cursor-pointer">
                 <input type="radio" name="payment_type" value="TUNAI" required class="peer hidden" checked onchange="togglePayment(this.value)">
                 <div class="border border-gray-200 rounded-lg p-3 text-center hover:bg-gray-50 peer-checked:border-shopee peer-checked:bg-orange-50 transition h-full flex flex-col items-center justify-center gap-1">
                     <i class="fas fa-money-bill-wave text-green-600"></i>
                     <span class="text-[10px] font-bold">COD/Tunai</span>
                 </div>
             </label>
             <label class="cursor-pointer">
                 <input type="radio" name="payment_type" value="TRANSFER" class="peer hidden" onchange="togglePayment(this.value)">
                 <div class="border border-gray-200 rounded-lg p-3 text-center hover:bg-gray-50 peer-checked:border-shopee peer-checked:bg-orange-50 transition h-full flex flex-col items-center justify-center gap-1">
                     <i class="fas fa-university text-blue-600"></i>
                     <span class="text-[10px] font-bold">Transfer</span>
                 </div>
             </label>
             <label class="cursor-pointer">
                 <input type="radio" name="payment_type" value="QRIS" class="peer hidden" onchange="togglePayment(this.value)">
                 <div class="border border-gray-200 rounded-lg p-3 text-center hover:bg-gray-50 peer-checked:border-shopee peer-checked:bg-orange-50 transition h-full flex flex-col items-center justify-center gap-1">
                     <i class="fas fa-qrcode text-gray-800"></i>
                     <span class="text-[10px] font-bold">QRIS</span>
                 </div>
             </label>
             <label class="cursor-pointer">
                 <input type="radio" name="payment_type" value="CORPORATE" class="peer hidden" onchange="togglePayment(this.value)">
                 <div class="border border-gray-200 rounded-lg p-3 text-center hover:bg-gray-50 peer-checked:border-shopee peer-checked:bg-orange-50 transition h-full flex flex-col items-center justify-center gap-1">
                     <i class="fas fa-business-time text-orange-500"></i>
                     <span class="text-[10px] font-bold">Corp</span>
                 </div>
             </label>
         </div>

         <!-- PAYMENT INFO DISPLAY -->
         <div id="payment_info_transfer" class="hidden mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3 animate-fade-in">
             <p class="text-[10px] text-gray-500 font-bold uppercase mb-1">Silakan Transfer ke:</p>
             <div class="flex items-center gap-3">
                 <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Bank_Central_Asia.svg/1200px-Bank_Central_Asia.svg.png" class="h-4 w-auto grayscale opacity-70">
                 <div>
                     <p class="font-mono font-bold text-gray-800 text-sm">862-065-8180</p>
                     <p class="text-[10px] text-gray-500">a.n Fadli Ardi</p>
                 </div>
                 <button type="button" onclick="navigator.clipboard.writeText('8620658180'); alert('No Rekening Disalin!')" class="ml-auto text-blue-600 text-xs font-bold">
                     <i class="fas fa-copy"></i>
                 </button>
             </div>
         </div>

         <div id="payment_info_qris" class="hidden mt-4 bg-gray-50 border border-gray-200 rounded-lg p-3 animate-fade-in text-center">
             <p class="text-[10px] text-gray-500 font-bold uppercase mb-2">Scan QRIS untuk Bayar</p>
             <div class="bg-white p-2 inline-block rounded shadow-sm">
                 <img src="{{ asset('storage/qirs.jpg') }}" class="w-full max-w-[300px] h-auto object-contain mx-auto" alt="QRIS Code">
             </div>
             <p class="text-[10px] text-gray-400 mt-1">Simpan bukti bayar untuk konfirmasi</p>
         </div>
    </div>

    <!-- RINCIAN PESANAN -->
    <div class="bg-white mt-2 p-4 shadow-sm border-y border-gray-100 mb-20">
        <h2 class="text-sm font-bold text-gray-800 mb-3 text-gray-800 flex items-center gap-2">
            <i class="fas fa-receipt text-gray-500"></i> Rincian Pesanan
        </h2>
        
        <div class="space-y-3 mb-4">
            @foreach($cart as $item)
            <div class="flex gap-3">
                 <div class="w-12 h-12 bg-gray-50 rounded border border-gray-100 overflow-hidden shrink-0">
                      @if($item['image']) <img src="{{ $item['image'] }}" class="w-full h-full object-cover"> @endif
                 </div>
                 <div class="flex-1">
                     <div class="flex justify-between items-start">
                         <h4 class="text-xs font-bold text-gray-800 line-clamp-1">{{ $item['name'] }}</h4>
                         <span class="text-xs font-bold">Rp {{ number_format($item['price'] * $item['qty'], 0, ',', '.') }}</span>
                     </div>
                     <p class="text-xs text-gray-500">{{ $item['qty'] }} x Rp {{ number_format($item['price'], 0, ',', '.') }}</p>
                 </div>
            </div>
            @endforeach
        </div>

        <div class="border-t border-dashed border-gray-200 pt-3 space-y-1">
            <div class="flex justify-between text-xs text-gray-600">
                <span>Subtotal Produk</span>
                <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-xs text-gray-600" id="delivery_row">
                <span>Ongkos Kirim</span>
                <span class="italic text-gray-400">Info Menyusul</span>
            </div>
             <div class="flex justify-between text-lg font-bold text-shopee pt-2">
                <span>Total Pembayaran</span>
                <span>Rp {{ number_format($subtotal, 0, ',', '.') }}</span>
            </div>
        </div>
        
        <textarea name="notes" placeholder="Catatan (Opsional, misal: Pagar Hitam)" rows="1"
            class="w-full mt-4 text-xs bg-gray-50 border border-gray-200 rounded p-2 outline-none focus:border-gray-300 transition"></textarea>
    </div>

    <!-- STICKY BOTTOM BUTTON -->
    <div class="fixed bottom-[64px] left-0 right-0 bg-white border-t border-gray-100 p-4 z-[60] max-w-md mx-auto shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
        <button type="submit" class="w-full bg-shopee text-white font-bold py-3.5 rounded-lg shadow-lg hover:bg-red-600 transition active:scale-95 flex justify-center items-center gap-2">
            <span>Buat Pesanan</span>
            <i class="fas fa-chevron-right text-xs"></i>
        </button>
    </div>

</form>

<script>
    function toggleAddress(type) {
        const addressSection = document.getElementById('address_section');
        const addressInput = document.getElementById('address_input');
        const deliveryRow = document.getElementById('delivery_row');
        // schedule section could be hidden too if self pickup, but maybe user wants to schedule pickup? 
        // Let's assume schedule is for delivery mostly.
        const scheduleSection = document.getElementById('schedule_section');

        if (type === 'SELF_PICKUP') {
            addressSection.style.display = 'none';
            addressInput.removeAttribute('required');
            addressInput.value = ''; 
            if(deliveryRow) deliveryRow.style.display = 'none';
            if(scheduleSection) scheduleSection.style.display = 'none';
        } else {
            addressSection.style.display = 'block';
            addressInput.setAttribute('required', 'required');
            if(deliveryRow) deliveryRow.style.display = 'flex';
             if(scheduleSection) scheduleSection.style.display = 'block';
        }
    }

    function togglePayment(type) {
        const transferInfo = document.getElementById('payment_info_transfer');
        const qrisInfo = document.getElementById('payment_info_qris');

        // Reset
        transferInfo.style.display = 'none';
        qrisInfo.style.display = 'none';

        if (type === 'TRANSFER') {
            transferInfo.style.display = 'block';
        } else if (type === 'QRIS') {
            qrisInfo.style.display = 'block';
        }
    }
    
    // Init state on load (in case browser caches selection)
    window.addEventListener('load', () => {
         const checkedPayment = document.querySelector('input[name="payment_type"]:checked');
         if(checkedPayment) togglePayment(checkedPayment.value);
    });
</script>
@endsection