<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depot Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Top Bar -->
    <div class="fixed top-0 left-0 right-0 bg-indigo-700 text-white shadow-lg z-50">
        <div class="flex justify-between items-center p-4">
            <h1 class="text-lg font-bold">Depot Panel</h1>
            <a href="{{ route('owner.logout') }}" class="bg-red-600 px-4 py-2 rounded-lg text-sm">
                Keluar
            </a>
        </div>
    </div>

    <div class="pt-20 px-4 pb-24">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Order Masuk</h2>
            <p class="text-4xl font-extrabold text-indigo-600 mt-2">{{ $orders->total() }}</p>
        </div>

        <div class="space-y-4">
            @forelse($orders as $order)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden" id="order-{{ $order->id }}">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4">
                        <div class="flex justify-between items-center">
                            <span class="font-bold">#{{ $order->order_number }}</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold status-badge"
                                  data-status="{{ $order->status }}">
                                {{ $order->status }}
                            </span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="text-3xl">Customer Icon</div>
                            <div>
                                <p class="font-bold text-lg">{{ $order->customer->name }}</p>
                                <a href="https://wa.me/62{{ substr($order->customer->phone_number, 1) }}" 
                                   class="text-sm text-green-600 font-medium">WhatsApp Icon {{ $order->customer->phone_number }}</a>
                            </div>
                        </div>

                        <div class="text-2xl font-bold text-green-600 mb-4">
                            Rp {{ number_format($order->total_amount) }}
                        </div>

                        <div class="text-sm text-gray-500 mb-4">
                            <i class="fas fa-clock"></i> {{ $order->created_at->format('d M Y H:i') }}
                        </div>

                        <!-- TOMBOl STATUS INTERAKTIF -->
                        <div class="grid grid-cols-2 gap-2">
                            @php
                                $statuses = ['DRAFT', 'PREPARED', 'READY', 'ON_DELIVERY', 'COMPLETE', 'CANCELLED'];
                                $colors = [
                                    'DRAFT' => 'gray',
                                    'PREPARED' => 'blue',
                                    'READY' => 'purple',
                                    'ON_DELIVERY' => 'orange',
                                    'COMPLETE' => 'green',
                                    'CANCELLED' => 'red'
                                ];
                            @endphp

                            @foreach($statuses as $status)
                                <button
                                    class="py-3 rounded-lg font-bold text-white transition {{ $order->status === $status ? 'ring-4 ring-white' : '' }}
                                           bg-{{ $colors[$status] }}-600 hover:bg-{{ $colors[$status] }}-700
                                           {{ $order->status === $status ? 'opacity-100' : 'opacity-70' }}"
                                    onclick="updateStatus({{ $order->id }}, '{{ $status }}')"
                                    {{ $order->status === $status ? 'disabled' : '' }}>
                                    @if($status == 'DRAFT') Draft
                                    @elseif($status == 'PREPARED') Siapkan
                                    @elseif($status == 'READY') Siap
                                    @elseif($status == 'ON_DELIVERY') Antar
                                    @elseif($status == 'COMPLETE') Selesai
                                    @elseif($status == 'CANCELLED') Batal
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-20">
                    <p class="text-6xl text-gray-300 mb-4">Inbox Icon</p>
                    <p class="text-xl text-gray-500">Belum ada order</p>
                </div>
            @endforelse
        </div>

        @if($orders->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $orders->links('pagination::tailwind') }}
            </div>
        </div>
        @endif
    </div>

    <!-- Floating Refresh -->
    <button onclick="location.reload()" class="fixed bottom-6 right-6 bg-indigo-600 text-white p-4 rounded-full shadow-2xl">
        Refresh Icon
    </button>

<script>
function updateStatus(orderId, newStatus) {
    Swal.fire({
        title: 'Ubah Status?',
        text: `Jadi ${newStatus === 'COMPLETE' ? 'SELESAI' : newStatus} ?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Ubah!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            // PAKE HARDCODE URL AJA — PASTI JALAN!
            fetch(`/owner/order/${orderId}/status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire('Sukses!', data.message, 'success');

                    // Update badge
                    const badge = document.querySelector(`#order-${orderId} .status-badge`);
                    badge.textContent = newStatus;

                    // Ganti warna badge
                    badge.className = badge.className.replace(/bg-\w+-600/, '');
                    const colors = { DRAFT:'gray', PREPARED:'blue', READY:'purple', ON_DELIVERY:'orange', COMPLETE:'green', CANCELLED:'red' };
                    badge.classList.add(`bg-${colors[newStatus]}-600`);

                    // Update tombol aktif
                    document.querySelectorAll(`#order-${orderId} button`).forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('ring-4', 'ring-white');
                        btn.classList.add('opacity-70');
                    });
                    event.target.disabled = true;
                    event.target.classList.add('ring-4', 'ring-white', 'opacity-100');
                    event.target.classList.remove('opacity-70');
                }
            })

        }
    });
}
</script>
</body>
</html>