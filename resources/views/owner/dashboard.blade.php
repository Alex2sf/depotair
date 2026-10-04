<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depot Panel - HydroExpert</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Top Bar -->
    <div class="fixed top-0 left-0 right-0 bg-indigo-700 text-white shadow-lg z-40">
        <div class="flex justify-between items-center p-4 max-w-7xl mx-auto">
            <div class="flex items-center gap-2 sm:gap-3">
                <h1 class="text-base sm:text-lg font-bold">Depot Panel</h1>
                <span class="text-[11px] bg-indigo-800 text-indigo-200 px-2 py-0.5 rounded-full font-semibold">HydroExpert</span>
            </div>
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Tombol Blast Header -->
                <button onclick="openBlastModal()" class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white font-bold px-3 py-1.5 sm:px-4 sm:py-2 rounded-xl shadow text-xs sm:text-sm flex items-center gap-1.5 transition transform active:scale-95">
                    <i class="fab fa-whatsapp text-sm sm:text-base"></i>
                    <span>Blast Belum Selesai</span>
                    <span class="bg-white/30 text-white text-[11px] sm:text-xs px-2 py-0.5 rounded-full font-extrabold" id="headerIncompleteBadge">{{ $incompleteCount }}</span>
                </button>
                <a href="{{ route('owner.logout') }}" class="bg-red-600 hover:bg-red-700 px-3 py-1.5 rounded-xl text-xs sm:text-sm font-semibold transition">
                    Keluar
                </a>
            </div>
        </div>
    </div>

    <div class="pt-20 px-4 pb-24 max-w-5xl mx-auto">
        <!-- Banner Peringatan Pesanan Belum Selesai -->
        @if($incompleteCount > 0)
        <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-600 text-white rounded-2xl p-4 sm:p-5 shadow-lg mb-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center text-2xl flex-shrink-0 shadow-inner">
                    <i class="fas fa-bell animate-bounce"></i>
                </div>
                <div>
                    <h3 class="font-black text-base sm:text-lg">Ada {{ $incompleteCount }} Pesanan Belum Selesai!</h3>
                    <p class="text-xs sm:text-sm text-amber-100">Pesanan DRAFT, Sedang Disiapkan, Siap, atau Sedang Diantar yang perlu difollow-up.</p>
                </div>
            </div>
            <button onclick="openBlastModal()" class="w-full sm:w-auto bg-white text-orange-600 hover:bg-orange-50 font-bold px-5 py-2.5 rounded-xl shadow-md text-sm flex items-center justify-center gap-2 transition transform active:scale-95 flex-shrink-0">
                <i class="fab fa-whatsapp text-lg text-green-600"></i>
                <span>Buka Blast WhatsApp</span>
            </button>
        </div>
        @endif

        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Order Masuk</h2>
            <p class="text-4xl font-extrabold text-indigo-600 mt-2">{{ $orders->total() }}</p>
        </div>

        <div class="space-y-4">
            @forelse($orders as $order)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100" id="order-{{ $order->id }}">
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-4">
                        <div class="flex justify-between items-center">
                            <span class="font-bold">#{{ $order->order_number }}</span>
                            <span class="px-3 py-1 rounded-full text-xs font-bold status-badge"
                                  data-status="{{ is_object($order->status) ? ($order->status->value ?? (string)$order->status) : (string)$order->status }}">
                                {{ is_object($order->status) ? ($order->status->value ?? (string)$order->status) : (string)$order->status }}
                            </span>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="p-5">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg font-bold">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <p class="font-bold text-lg text-gray-900">{{ $order->customer->name ?? 'Pelanggan' }}</p>
                                    @php
                                        $rawPhone = $order->customer->phone_number ?? '';
                                        $cleanWa = preg_replace('/[^0-9]/', '', $rawPhone);
                                        if (str_starts_with($cleanWa, '0')) {
                                            $cleanWa = '62' . substr($cleanWa, 1);
                                        } elseif (str_starts_with($cleanWa, '8')) {
                                            $cleanWa = '62' . $cleanWa;
                                        }
                                    @endphp
                                    <a href="https://wa.me/{{ $cleanWa }}" target="_blank"
                                       class="text-sm text-green-600 hover:text-green-700 font-medium inline-flex items-center gap-1">
                                        <i class="fab fa-whatsapp"></i> {{ $rawPhone }}
                                    </a>
                                </div>
                            </div>

                            @if(!in_array(is_object($order->status) ? $order->status->value : (string)$order->status, ['COMPLETE', 'CANCELLED']))
                            <button onclick="blastSingleOrderDirect({{ $order->id }})" class="bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1.5 transition">
                                <i class="fab fa-whatsapp text-emerald-600"></i>
                                <span>Blast WA</span>
                            </button>
                            @endif
                        </div>

                        <div class="text-2xl font-bold text-green-600 mb-2">
                            Rp {{ number_format($order->total_amount) }}
                        </div>

                        <div class="text-sm text-gray-500 mb-4 flex items-center gap-2">
                            <i class="fas fa-clock"></i> {{ $order->created_at->format('d M Y H:i') }}
                            <span class="text-gray-300">•</span>
                            <span class="font-semibold text-gray-700">{{ is_object($order->order_type) ? $order->order_type->value : $order->order_type }}</span>
                            <span class="text-gray-300">•</span>
                            <span class="font-semibold text-gray-700">{{ is_object($order->payment_type) ? $order->payment_type->value : $order->payment_type }}</span>
                        </div>

                        <!-- TOMBOL STATUS INTERAKTIF -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
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
                                $curStatus = is_object($order->status) ? ($order->status->value ?? (string)$order->status) : (string)$order->status;
                            @endphp

                            @foreach($statuses as $status)
                                <button
                                    class="py-2.5 rounded-lg font-bold text-white text-xs sm:text-sm transition {{ $curStatus === $status ? 'ring-4 ring-offset-1 ring-indigo-400 opacity-100' : 'opacity-70 hover:opacity-90' }}
                                           bg-{{ $colors[$status] }}-600 hover:bg-{{ $colors[$status] }}-700"
                                    onclick="updateStatus({{ $order->id }}, '{{ $status }}')"
                                    {{ $curStatus === $status ? 'disabled' : '' }}>
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
                <div class="text-center py-20 bg-white rounded-2xl shadow">
                    <p class="text-6xl text-gray-300 mb-4"><i class="fas fa-inbox"></i></p>
                    <p class="text-xl text-gray-500 font-semibold">Belum ada order</p>
                </div>
            @endforelse
        </div>

        @if($orders->hasPages())
            <div class="mt-8 flex justify-center">
                {{ $orders->links('pagination::tailwind') }}
            </div>
        @endif
    </div>

    <!-- Floating Buttons (Refresh & Blast) -->
    <div class="fixed bottom-6 right-6 flex flex-col gap-3 z-30">
        <button onclick="openBlastModal()" class="bg-gradient-to-r from-emerald-500 to-green-600 hover:from-emerald-600 hover:to-green-700 text-white p-4 rounded-full shadow-2xl flex items-center justify-center transition transform hover:scale-105 active:scale-95" title="Blast Pesanan Belum Selesai">
            <i class="fab fa-whatsapp text-2xl"></i>
        </button>
        <button onclick="location.reload()" class="bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-full shadow-2xl flex items-center justify-center transition transform hover:scale-105 active:scale-95" title="Muat Ulang Halaman">
            <i class="fas fa-rotate text-xl"></i>
        </button>
    </div>

    <!-- MODAL BLAST WHATSAPP PESANAN BELUM SELESAI -->
    <div id="blastModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl overflow-hidden my-auto border border-gray-100 flex flex-col max-h-[92vh]">
            <!-- Header Modal -->
            <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-700 text-white p-5 flex justify-between items-center flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-xl shadow-inner">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div>
                        <h3 class="font-black text-lg">Blast Pesanan Belum Selesai</h3>
                        <p class="text-xs text-emerald-100">Kirim follow-up / notifikasi status pesanan via WhatsApp</p>
                    </div>
                </div>
                <button onclick="closeBlastModal()" class="text-white/80 hover:text-white p-1 text-2xl leading-none">
                    &times;
                </button>
            </div>

            <!-- Body Modal -->
            <div class="p-5 space-y-4 overflow-y-auto flex-1">
                <!-- Ringkasan Info Box -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-3.5">
                        <span class="text-xs text-emerald-700 font-bold block">Pesanan Belum Selesai</span>
                        <span class="text-2xl font-black text-emerald-800" id="modalIncompleteCount">{{ $incompleteCount }}</span>
                        <span class="text-[11px] text-emerald-600 block mt-0.5 font-medium">DRAFT, Siap, atau Diantar</span>
                    </div>
                    <div class="bg-blue-50 border border-blue-100 rounded-xl p-3.5">
                        <span class="text-xs text-blue-700 font-bold block">Total Tagihan Tertunda</span>
                        <span class="text-2xl font-black text-blue-800">Rp {{ number_format($incompleteOrders->sum('total_amount'), 0, ',', '.') }}</span>
                        <span class="text-[11px] text-blue-600 block mt-0.5 font-medium">Dari {{ $incompleteCount }} pesanan</span>
                    </div>
                </div>

                <!-- Template Pesan WhatsApp -->
                <div>
                    <div class="flex justify-between items-center mb-1.5">
                        <label class="text-xs font-bold text-gray-700">Template Pesan WhatsApp:</label>
                        <button type="button" onclick="resetDefaultTemplate()" class="text-[11px] text-indigo-600 hover:text-indigo-800 font-semibold">Reset Template</button>
                    </div>
                    <textarea id="blastTemplate" rows="4" class="w-full text-xs sm:text-sm border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 font-mono text-gray-800 transition">Halo kak {nama}, kami dari {depot}! 💧
Menginfokan pesanan Anda #{nomor_order} saat ini berstatus: *{status}*.
Total tagihan: {total}.

Mohon ditunggu ya kak, pesanan sedang diproses. Jika ada pertanyaan silakan balas pesan ini. Terima kasih! 🙏</textarea>
                    
                    <!-- Tag Variabel Chips -->
                    <div class="flex flex-wrap items-center gap-1.5 mt-2">
                        <span class="text-[11px] text-gray-500 font-medium">Sisipkan variabel:</span>
                        <button type="button" onclick="insertVariable('{nama}')" class="text-[11px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-mono font-bold transition">{nama}</button>
                        <button type="button" onclick="insertVariable('{nomor_order}')" class="text-[11px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-mono font-bold transition">{nomor_order}</button>
                        <button type="button" onclick="insertVariable('{status}')" class="text-[11px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-mono font-bold transition">{status}</button>
                        <button type="button" onclick="insertVariable('{total}')" class="text-[11px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-mono font-bold transition">{total}</button>
                        <button type="button" onclick="insertVariable('{depot}')" class="text-[11px] bg-gray-100 hover:bg-gray-200 text-gray-700 px-2 py-0.5 rounded font-mono font-bold transition">{depot}</button>
                    </div>
                </div>

                <!-- Pengaturan Gateway (Opsional) -->
                <div class="border border-gray-200 rounded-xl p-3 bg-gray-50/70">
                    <button type="button" onclick="toggleGatewaySettings()" class="w-full flex justify-between items-center text-xs font-bold text-gray-700 text-left">
                        <span class="flex items-center gap-1.5">
                            <i class="fas fa-cog text-gray-400"></i>
                            <span>Gunakan Gateway WA API (Opsional: Kirim Otomatis di Background)</span>
                        </span>
                        <i class="fas fa-chevron-down text-gray-400 transition transform" id="gatewayArrow"></i>
                    </button>
                    <div id="gatewaySection" class="hidden mt-3 pt-3 border-t border-gray-200 space-y-2">
                        <p class="text-[11px] text-gray-500">
                            Masukkan Token <strong>Fonnte</strong> Anda jika ingin pesan ditembak otomatis dari server. Jika dikosongkan, sistem akan membuka WhatsApp langsung untuk setiap pesanan.
                        </p>
                        <input type="text" id="gatewayToken" placeholder="Contoh: vL7xK9... (Token Fonnte)" class="w-full text-xs border border-gray-300 rounded-lg p-2.5 focus:ring-1 focus:ring-emerald-500">
                    </div>
                </div>

                <!-- Daftar Pesanan Belum Selesai -->
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <div class="flex items-center gap-2">
                            <input type="checkbox" id="selectAllOrders" onchange="toggleSelectAll(this)" checked class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer">
                            <label for="selectAllOrders" class="text-xs font-bold text-gray-700 cursor-pointer">Pilih Semua (<span id="selectedCountText">{{ $incompleteCount }}</span> terpilih)</label>
                        </div>
                        <button type="button" onclick="copyRecap()" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-2.5 py-1 rounded-lg flex items-center gap-1 transition">
                            <i class="fas fa-copy"></i>
                            <span>Salin Rekap</span>
                        </button>
                    </div>

                    <div class="space-y-2 max-h-56 overflow-y-auto pr-1" id="orderCheckboxList">
                        @forelse($incompleteOrders as $incOrder)
                            @php
                                $incStatus = is_object($incOrder->status) ? ($incOrder->status->value ?? (string)$incOrder->status) : (string)$incOrder->status;
                                $statusBadgeColor = match($incStatus) {
                                    'DRAFT' => 'bg-gray-100 text-gray-700',
                                    'PREPARED' => 'bg-blue-100 text-blue-700',
                                    'READY' => 'bg-purple-100 text-purple-700',
                                    'ON_DELIVERY' => 'bg-orange-100 text-orange-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp
                            <div class="border border-gray-200 rounded-xl p-3 hover:border-emerald-300 bg-white flex items-center justify-between gap-3 transition order-item-card" id="card-inc-{{ $incOrder->id }}">
                                <div class="flex items-center gap-3">
                                    <input type="checkbox" class="order-checkbox rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 w-4 h-4 cursor-pointer" value="{{ $incOrder->id }}" checked onchange="updateSelectedCount()">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-sm text-gray-900">#{{ $incOrder->order_number }}</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $statusBadgeColor }}">
                                                {{ $incStatus }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-600 font-medium mt-0.5">
                                            {{ $incOrder->customer->name ?? 'Pelanggan' }} • <span class="font-mono text-gray-500">{{ $incOrder->customer->phone_number ?? '-' }}</span>
                                        </div>
                                        <div class="text-xs font-bold text-emerald-600">
                                            Rp {{ number_format($incOrder->total_amount) }}
                                        </div>
                                    </div>
                                </div>
                                <button type="button" onclick="blastSingleOrderDirect({{ $incOrder->id }})" class="flex-shrink-0 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 font-bold px-3 py-1.5 rounded-lg text-xs flex items-center gap-1 transition">
                                    <i class="fab fa-whatsapp text-emerald-600"></i>
                                    <span>Kirim</span>
                                </button>
                            </div>
                        @empty
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-check-circle text-3xl mb-2 text-green-500"></i>
                                <p class="text-sm font-semibold">Semua pesanan sudah selesai!</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="p-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-3 flex-shrink-0">
                <button type="button" onclick="closeBlastModal()" class="w-full sm:w-auto px-4 py-2 border border-gray-300 text-gray-700 rounded-xl text-sm font-semibold hover:bg-gray-100 transition">
                    Tutup
                </button>
                <div class="w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                    <button type="button" onclick="startBlastProcess()" id="btnBlastSubmit" class="w-full sm:w-auto bg-gradient-to-r from-emerald-600 to-green-600 hover:from-emerald-700 hover:to-green-700 text-white font-bold px-5 py-2.5 rounded-xl text-sm shadow-md flex items-center justify-center gap-2 transition transform active:scale-95 disabled:opacity-50">
                        <i class="fab fa-whatsapp text-lg"></i>
                        <span id="btnBlastText">Blast Terpilih (<span id="btnCountBadge">{{ $incompleteCount }}</span>)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
// Data incomplete orders untuk client-side helper
window.incompleteOrdersData = [
    @foreach($incompleteOrders as $o)
    {
        id: {{ $o->id }},
        order_number: "{{ $o->order_number }}",
        status: "{{ is_object($o->status) ? ($o->status->value ?? (string)$o->status) : (string)$o->status }}",
        status_label: "{{ match(is_object($o->status) ? ($o->status->value ?? (string)$o->status) : (string)$o->status) { 'DRAFT' => 'Draft / Baru Masuk', 'PREPARED' => 'Sedang Disiapkan Staff', 'READY' => 'Siap (Menunggu Kurir / Diambil)', 'ON_DELIVERY' => 'Sedang Dalam Pengantaran', default => (string)$o->status } }}",
        customer_name: "{{ addslashes($o->customer->name ?? 'Pelanggan') }}",
        phone: "{{ $o->customer->phone_number ?? '' }}",
        total: {{ (int)$o->total_amount }},
        total_formatted: "Rp {{ number_format($o->total_amount, 0, ',', '.') }}",
    },
    @endforeach
];

// Open / Close Modal
function openBlastModal() {
    document.getElementById('blastModal').classList.remove('hidden');
    updateSelectedCount();
}

function closeBlastModal() {
    document.getElementById('blastModal').classList.add('hidden');
}

// Toggle Gateway Settings Dropdown
function toggleGatewaySettings() {
    const section = document.getElementById('gatewaySection');
    const arrow = document.getElementById('gatewayArrow');
    section.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

// Checkbox Logic
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.order-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}

function updateSelectedCount() {
    const checked = document.querySelectorAll('.order-checkbox:checked');
    const total = document.querySelectorAll('.order-checkbox');
    const count = checked.length;

    document.getElementById('selectedCountText').textContent = count;
    document.getElementById('btnCountBadge').textContent = count;
    document.getElementById('selectAllOrders').checked = (count === total.length && total.length > 0);

    const submitBtn = document.getElementById('btnBlastSubmit');
    if (count === 0) {
        submitBtn.disabled = true;
    } else {
        submitBtn.disabled = false;
    }
}

// Variable Insertion into Textarea
function insertVariable(tag) {
    const textarea = document.getElementById('blastTemplate');
    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const text = textarea.value;
    textarea.value = text.substring(0, start) + tag + text.substring(end);
    textarea.focus();
    textarea.selectionStart = textarea.selectionEnd = start + tag.length;
}

// Reset Default Template
function resetDefaultTemplate() {
    document.getElementById('blastTemplate').value = 
`Halo kak {nama}, kami dari {depot}! 💧
Menginfokan pesanan Anda #{nomor_order} saat ini berstatus: *{status}*.
Total tagihan: {total}.

Mohon ditunggu ya kak, pesanan sedang diproses. Jika ada pertanyaan silakan balas pesan ini. Terima kasih! 🙏`;
}

// Clean Indonesian phone number to international 62xxx
function cleanPhoneWa(phone) {
    if (!phone) return '';
    let p = String(phone).replace(/[^0-9]/g, '');
    if (p.startsWith('0')) {
        p = '62' + p.substring(1);
    } else if (p.startsWith('8')) {
        p = '62' + p;
    }
    return p;
}

// Build text message for an order
function buildMessage(order, template) {
    return template
        .replace(/{nama}/g, order.customer_name || 'Pelanggan')
        .replace(/{nomor_order}/g, order.order_number)
        .replace(/{status}/g, order.status_label || order.status)
        .replace(/{total}/g, order.total_formatted)
        .replace(/{depot}/g, 'HydroExpert');
}

// Kirim single order langsung
function blastSingleOrderDirect(orderId) {
    const order = window.incompleteOrdersData.find(o => o.id === orderId);
    if (!order) {
        Swal.fire('Error', 'Data pesanan tidak ditemukan', 'error');
        return;
    }

    const phone = cleanPhoneWa(order.phone);
    if (!phone) {
        Swal.fire('Perhatian', 'Nomor WhatsApp pelanggan tidak valid atau kosong.', 'warning');
        return;
    }

    const template = document.getElementById('blastTemplate').value;
    const message = buildMessage(order, template);
    const waUrl = 'https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(message);

    window.open(waUrl, '_blank');
}

// Salin Rekap Semua Pesanan
function copyRecap() {
    if (!window.incompleteOrdersData || window.incompleteOrdersData.length === 0) {
        Swal.fire('Kosong', 'Tidak ada pesanan belum selesai.', 'info');
        return;
    }

    let recap = `*REKAP PESANAN BELUM SELESAI - HYDROEXPERT*\n`;
    recap += `Waktu: ${new Date().toLocaleString('id-ID')}\n\n`;

    window.incompleteOrdersData.forEach((o, idx) => {
        recap += `${idx + 1}. #${o.order_number} | ${o.status_label}\n`;
        recap += `   Nama: ${o.customer_name} (${o.phone || '-'})\n`;
        recap += `   Total: ${o.total_formatted}\n\n`;
    });

    navigator.clipboard.writeText(recap).then(() => {
        Swal.fire({
            title: 'Berhasil Disalin!',
            text: 'Rekap ' + window.incompleteOrdersData.length + ' pesanan telah disalin ke clipboard.',
            icon: 'success',
            timer: 2000,
            showConfirmButton: false
        });
    }).catch(err => {
        Swal.fire('Gagal', 'Tidak dapat menyalin ke clipboard: ' + err, 'error');
    });
}

// Jalankan proses blast (via Gateway atau Buka WA Berurutan)
function startBlastProcess() {
    const checkedBoxes = Array.from(document.querySelectorAll('.order-checkbox:checked'));
    if (checkedBoxes.length === 0) {
        Swal.fire('Pilih Pesanan', 'Silakan centang minimal 1 pesanan untuk diblast.', 'warning');
        return;
    }

    const selectedIds = checkedBoxes.map(cb => parseInt(cb.value));
    const selectedOrders = window.incompleteOrdersData.filter(o => selectedIds.includes(o.id));
    const template = document.getElementById('blastTemplate').value;
    const gatewayToken = document.getElementById('gatewayToken').value.trim();

    // JIKA ADA TOKEN GATEWAY API DIIISI: Kirim via Server API
    if (gatewayToken) {
        Swal.fire({
            title: 'Kirim Blast via Gateway?',
            html: `Akan mengirim pesan otomatis ke <b>${selectedOrders.length}</b> pelanggan via WhatsApp Gateway API.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Kirim Sekarang!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#10b981'
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Sedang Mengirim...',
                    html: 'Mohon tunggu beberapa saat...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                fetch("{{ route('owner.blastOrders') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        order_ids: selectedIds,
                        template: template,
                        gateway_token: gatewayToken
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Blast Selesai!',
                            html: `<b>Berhasil terkirim:</b> ${data.success_count} pesan<br><b>Gagal:</b> ${data.failed_count} pesan`,
                            icon: data.failed_count === 0 ? 'success' : 'warning',
                        });
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan sistem', 'error');
                    }
                })
                .catch(err => {
                    Swal.fire('Error', 'Gagal menghubungi server: ' + err, 'error');
                });
            }
        });
        return;
    }

    // JIKA TANPA GATEWAY API: Gunakan Pemandu Kirim WhatsApp Web Interaktif
    let currentIndex = 0;

    function promptNextOrder() {
        if (currentIndex >= selectedOrders.length) {
            Swal.fire('Selesai!', 'Semua pesanan yang dipilih telah selesai diproses.', 'success');
            return;
        }

        const currentOrder = selectedOrders[currentIndex];
        const phone = cleanPhoneWa(currentOrder.phone);
        const msg = buildMessage(currentOrder, template);
        const waUrl = 'https://api.whatsapp.com/send?phone=' + phone + '&text=' + encodeURIComponent(msg);

        Swal.fire({
            title: `Kirim WA (${currentIndex + 1} dari ${selectedOrders.length})`,
            html: `
                <div class="text-left text-xs bg-gray-50 p-3 rounded-xl border border-gray-200 mb-3">
                    <p class="font-bold text-gray-800">#${currentOrder.order_number} • ${currentOrder.customer_name}</p>
                    <p class="text-gray-600">Nomor: ${currentOrder.phone || '-'}</p>
                    <p class="text-emerald-600 font-bold mt-1">${currentOrder.total_formatted}</p>
                </div>
                <p class="text-xs text-gray-500">Klik <b>Buka WhatsApp</b> untuk mengirim pesan ke nomor ini.</p>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: '<i class="fab fa-whatsapp mr-1"></i> Buka WhatsApp',
            cancelButtonText: 'Lewati',
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#6b7280',
            showDenyButton: true,
            denyButtonText: 'Selesai / Tutup'
        }).then(result => {
            if (result.isConfirmed) {
                window.open(waUrl, '_blank');
                currentIndex++;
                setTimeout(promptNextOrder, 400);
            } else if (result.dismiss === Swal.DismissReason.cancel) {
                currentIndex++;
                promptNextOrder();
            }
        });
    }

    promptNextOrder();
}

// Update Status Order via AJAX
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
                    if (badge) {
                        badge.textContent = newStatus;
                        badge.className = badge.className.replace(/bg-\w+-600/, '');
                        const colors = { DRAFT:'gray', PREPARED:'blue', READY:'purple', ON_DELIVERY:'orange', COMPLETE:'green', CANCELLED:'red' };
                        badge.classList.add(`bg-${colors[newStatus]}-600`);
                    }

                    // Update buttons
                    document.querySelectorAll(`#order-${orderId} button`).forEach(btn => {
                        btn.disabled = false;
                        btn.classList.remove('ring-4', 'ring-offset-1', 'ring-indigo-400', 'opacity-100');
                        btn.classList.add('opacity-70');
                    });
                    if (event && event.target) {
                        event.target.disabled = true;
                        event.target.classList.add('ring-4', 'ring-offset-1', 'ring-indigo-400', 'opacity-100');
                        event.target.classList.remove('opacity-70');
                    }

                    // Reload jika status jadi COMPLETE atau CANCELLED agar count update
                    if (newStatus === 'COMPLETE' || newStatus === 'CANCELLED') {
                        setTimeout(() => location.reload(), 1200);
                    }
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Gagal mengubah status: ' + err, 'error');
            });
        }
    });
}
</script>
</body>
</html>