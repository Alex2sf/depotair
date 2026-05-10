<div class="text-center py-20">
    <h1 class="text-5xl font-bold text-green-600 mb-6">Pesanan Berhasil!</h1>
    <p class="text-3xl mb-4">No. Pesanan: <strong class="font-mono">{{ $order->order_number }}</strong></p>
    <p class="text-xl mb-8">Simpan nomor ini untuk cek status kapan saja</p>
    
    <a href="/track" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-4 rounded-xl text-xl font-bold inline-block">
        Cek Status Pesanan
    </a>
    
    <div class="mt-8">
        <a href="/" class="text-indigo-600 hover:underline text-lg">← Pesan Lagi</a>
    </div>
</div>