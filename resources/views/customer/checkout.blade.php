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

    @if($errors->any())
    <div class="mx-4 mt-3 bg-red-50 border border-red-200 text-red-700 p-3.5 rounded-xl text-xs flex items-start gap-2.5 shadow-sm">
        <i class="fas fa-exclamation-triangle text-red-500 text-sm mt-0.5 shrink-0"></i>
        <div>
            <p class="font-bold mb-1 text-red-800">Periksa Kembali Data Anda:</p>
            <ul class="list-disc list-inside space-y-0.5 text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif
    
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
             <!-- Nomor WhatsApp (Kunci Utama Auto-fill) -->
             <div>
                 <div class="flex justify-between items-center mb-1">
                     <label class="text-[11px] font-bold text-gray-700 flex items-center gap-1">
                         <i class="fab fa-whatsapp text-green-600 text-sm"></i> Nomor WhatsApp
                     </label>
                     <span class="text-[10px] text-gray-400">Ketik untuk auto-fill data</span>
                 </div>
                 <div class="relative flex items-center">
                     <input type="tel" name="phone" id="phone_input" placeholder="contoh: 08123456789" required 
                         pattern="[0-9]{9,15}" title="Nomor WhatsApp harus berupa 9 sampai 15 digit angka"
                         value="{{ old('phone') }}"
                         class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent pr-8"
                         autocomplete="tel">
                     <span id="phone_spinner" class="hidden absolute right-2 text-shopee text-xs">
                         <i class="fas fa-spinner fa-spin"></i>
                     </span>
                     <span id="phone_check" class="hidden absolute right-2 text-green-600 text-sm">
                         <i class="fas fa-check-circle"></i>
                     </span>
                 </div>
                 <!-- Status Banner Auto-fill -->
                 <div id="customer_status_banner" class="hidden mt-2 p-2.5 rounded-lg text-xs transition"></div>
             </div>

             <!-- Nama Lengkap -->
             <div>
                 <label class="text-[11px] font-bold text-gray-700 flex items-center gap-1 mb-1">
                     <i class="far fa-user text-gray-500 text-xs"></i> Nama Lengkap
                 </label>
                 <input type="text" name="name" id="name_input" placeholder="Nama Anda" required 
                     value="{{ old('name') }}"
                     class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent"
                     autocomplete="name">
             </div>
             
             <!-- Alamat Pengantaran -->
             <div id="address_section">
                 <label class="text-[11px] font-bold text-gray-700 flex items-center gap-1 mb-1">
                     <i class="fas fa-home text-gray-500 text-xs"></i> Alamat Pengiriman
                 </label>
                 <textarea name="address" id="address_input" placeholder="Alamat Lengkap (Jalan, No Rumah, RT/RW, Patokan)" rows="2" required
                     class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent resize-none">{{ old('address') }}</textarea>
                 
                 <div class="mt-2">
                     <label class="text-[10px] text-gray-500 font-semibold block mb-0.5">Link Google Maps (Opsional)</label>
                     <input type="url" name="address_link" id="address_link_input" placeholder="https://maps.app.goo.gl/..." 
                         value="{{ old('address_link') }}"
                         class="w-full text-sm border-b border-gray-200 focus:border-shopee py-2 outline-none transition bg-transparent">
                 </div>
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

    // Mencegah double submit order di web browser & simpan ke LocalStorage
    const checkoutForm = document.getElementById('checkoutForm');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const btn = checkoutForm.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.classList.add('opacity-75', 'cursor-not-allowed');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Memproses Pesanan...';
            }

            // Simpan info ke localStorage agar pesanan berikutnya otomatis terisi
            try {
                const phoneVal = document.getElementById('phone_input')?.value;
                const nameVal = document.getElementById('name_input')?.value;
                const addressVal = document.getElementById('address_input')?.value;
                const mapsVal = document.getElementById('address_link_input')?.value;

                if (phoneVal) localStorage.setItem('depot_phone', phoneVal);
                if (nameVal) localStorage.setItem('depot_name', nameVal);
                if (addressVal) localStorage.setItem('depot_address', addressVal);
                if (mapsVal) localStorage.setItem('depot_maps', mapsVal);
            } catch (_) {}
        });
    }

    // =========================================================================
    // SMART AUTO-FILL BY NOMOR WHATSAPP (PELANGGAN LAMA & BARU)
    // =========================================================================
    const phoneInput = document.getElementById('phone_input');
    const nameInput = document.getElementById('name_input');
    const addressInput = document.getElementById('address_input');
    const mapsInput = document.getElementById('address_link_input');
    const phoneSpinner = document.getElementById('phone_spinner');
    const phoneCheck = document.getElementById('phone_check');
    const statusBanner = document.getElementById('customer_status_banner');

    let lastCheckedPhone = '';
    let lookupTimer = null;

    async function checkCustomerPhone(phone) {
        const clean = phone.replace(/[^0-9]/g, '');
        if (clean.length < 9) {
            if (statusBanner) statusBanner.classList.add('hidden');
            if (phoneCheck) phoneCheck.classList.add('hidden');
            return;
        }

        if (clean === lastCheckedPhone) return;
        lastCheckedPhone = clean;

        if (phoneSpinner) phoneSpinner.classList.remove('hidden');
        if (phoneCheck) phoneCheck.classList.add('hidden');

        try {
            const res = await fetch('/customer/lookup?phone=' + encodeURIComponent(clean));
            const data = await res.json();

            if (phoneSpinner) phoneSpinner.classList.add('hidden');

            if (data.success && data.found && data.customer) {
                // Auto-fill form fields
                if (nameInput) nameInput.value = data.customer.name || '';
                if (addressInput) addressInput.value = data.customer.address || '';
                if (mapsInput && data.customer.address_link) {
                    mapsInput.value = data.customer.address_link;
                }

                if (phoneCheck) phoneCheck.classList.remove('hidden');

                if (statusBanner) {
                    statusBanner.className = 'mt-2 p-2.5 rounded-lg text-xs flex items-center gap-2 bg-green-50 text-green-800 border border-green-200';
                    statusBanner.innerHTML = '<i class="fas fa-check-circle text-green-600 text-sm shrink-0"></i> <div>Halo <strong>' + (data.customer.name || 'Kak') + '</strong>! Data & alamat Anda otomatis terisi dari pesanan sebelumnya.</div>';
                    statusBanner.classList.remove('hidden');
                }
            } else {
                if (phoneCheck) phoneCheck.classList.add('hidden');
                if (statusBanner) {
                    statusBanner.className = 'mt-2 p-2.5 rounded-lg text-xs flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200';
                    statusBanner.innerHTML = '<i class="fas fa-user-plus text-blue-600 text-sm shrink-0"></i> <div>👋 Halo pelanggan baru! Silakan lengkapi nama & alamat di bawah ini ya.</div>';
                    statusBanner.classList.remove('hidden');
                }
            }
        } catch (e) {
            if (phoneSpinner) phoneSpinner.classList.add('hidden');
        }
    }

    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            clearTimeout(lookupTimer);
            const val = this.value;
            lookupTimer = setTimeout(() => checkCustomerPhone(val), 400);
        });

        phoneInput.addEventListener('blur', function() {
            clearTimeout(lookupTimer);
            checkCustomerPhone(this.value);
        });
    }

    // Auto-fill dari LocalStorage atau URL Parameter (e.g. ?phone=08123... atau ?hp=08123...)
    window.addEventListener('DOMContentLoaded', () => {
        try {
            const urlParams = new URLSearchParams(window.location.search);
            const urlPhone = urlParams.get('phone') || urlParams.get('hp');

            if (urlPhone && phoneInput) {
                phoneInput.value = urlPhone;
                checkCustomerPhone(urlPhone);
            } else if (phoneInput && (!phoneInput.value || phoneInput.value.trim() === '')) {
                const savedPhone = localStorage.getItem('depot_phone');
                const savedName = localStorage.getItem('depot_name');
                const savedAddress = localStorage.getItem('depot_address');
                const savedMaps = localStorage.getItem('depot_maps');

                if (savedPhone) {
                    phoneInput.value = savedPhone;
                    if (savedName && nameInput) nameInput.value = savedName;
                    if (savedAddress && addressInput) addressInput.value = savedAddress;
                    if (savedMaps && mapsInput) mapsInput.value = savedMaps;
                    checkCustomerPhone(savedPhone);
                }
            }
        } catch (_) {}
    });
</script>
@endsection