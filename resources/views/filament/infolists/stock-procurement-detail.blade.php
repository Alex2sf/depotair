@php
    $p = $getRecord();
    $p->loadMissing(['items.product', 'requester', 'approver', 'receiver']);
    $rp = fn ($n) => 'Rp ' . number_format((int) $n, 0, ',', '.');
    $num = fn ($n) => number_format((int) $n, 0, ',', '.');
    $status = $p->status;
    $statusLabel = \App\Filament\Resources\StockProcurements\StockProcurementResource::STATUS_LABELS[$status] ?? $status;
    $statusClass = ['PENDING' => 'warn', 'APPROVED' => 'info', 'RECEIVED' => 'ok', 'REJECTED' => 'bad'][$status] ?? '';
    $approvedLike = in_array($status, ['APPROVED', 'RECEIVED'], true);

    $items = $p->items;
    $totalRequested = $items->sum('subtotal_requested');
    $totalApproved = $approvedLike ? $items->where('is_approved', true)->sum('subtotal_approved') : null;

    // Langkah alur: [judul, waktu, orang, keadaan: done|current|todo|bad|skip]
    $steps = [
        ['Diajukan', $p->created_at, $p->requester?->name, 'done'],
        match ($status) {
            'REJECTED' => ['Ditolak', $p->approved_at, $p->approver?->name, 'bad'],
            'PENDING' => ['Menunggu persetujuan owner', null, null, 'current'],
            default => ['Disetujui · Kas Besar dipotong', $p->approved_at, $p->approver?->name, 'done'],
        },
        match ($status) {
            'RECEIVED' => ['Barang diterima · stok bertambah', $p->received_at, $p->receiver?->name, 'done'],
            'APPROVED' => ['Menunggu barang datang', null, null, 'current'],
            'REJECTED' => ['Tidak dilanjutkan', null, null, 'skip'],
            default => ['Barang diterima', null, null, 'todo'],
        },
    ];
@endphp

<style>
    .pd { --pd-border: var(--gray-200, #e5e7eb); --pd-muted: var(--gray-500, #6b7280); --pd-text: var(--gray-950, #030712);
          --pd-bg: #fff; --pd-soft: var(--gray-50, #f9fafb); --pd-head: var(--gray-100, #f3f4f6);
          --pd-ok: var(--success-600, #16a34a); --pd-bad: var(--danger-600, #dc2626); --pd-warn: var(--warning-600, #d97706); --pd-info: var(--info-600, #2563eb); }
    .dark .pd { --pd-border: rgba(255,255,255,.1); --pd-muted: var(--gray-400, #9ca3af); --pd-text: #fff; --pd-bg: var(--gray-900, #111827);
                --pd-soft: rgba(255,255,255,.03); --pd-head: var(--gray-800, #1f2937);
                --pd-ok: var(--success-400, #4ade80); --pd-bad: var(--danger-400, #f87171); --pd-warn: var(--warning-400, #fbbf24); --pd-info: var(--info-400, #60a5fa); }
    .pd, .pd * { box-sizing: border-box; }
    .pd { color: var(--pd-text); font-size: 14px; display: flex; flex-direction: column; gap: 16px; }
    .pd-card { background: var(--pd-bg); border: 1px solid var(--pd-border); border-radius: 12px; padding: 16px 18px; }
    .pd-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: var(--pd-muted); margin: 0 0 12px; }
    .pd-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; }
    .pd-no { font-size: 22px; font-weight: 800; letter-spacing: .01em; }
    .pd-badge { display: inline-block; padding: 3px 11px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap;
                background: color-mix(in srgb, var(--pd-muted) 16%, transparent); color: var(--pd-muted); }
    .pd-badge.ok { background: color-mix(in srgb, var(--pd-ok) 15%, transparent); color: var(--pd-ok); }
    .pd-badge.bad { background: color-mix(in srgb, var(--pd-bad) 15%, transparent); color: var(--pd-bad); }
    .pd-badge.warn { background: color-mix(in srgb, var(--pd-warn) 17%, transparent); color: var(--pd-warn); }
    .pd-badge.info { background: color-mix(in srgb, var(--pd-info) 15%, transparent); color: var(--pd-info); }
    .pd-badge.big { font-size: 13px; padding: 5px 14px; }
    .pd-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; margin-top: 14px; }
    .pd-stat { background: var(--pd-soft); border: 1px solid var(--pd-border); border-radius: 10px; padding: 12px 14px; }
    .pd-stat small { display: block; color: var(--pd-muted); font-size: 12px; margin-bottom: 4px; }
    .pd-stat strong { font-size: 20px; font-weight: 800; }
    .pd-steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
    .pd-step { position: relative; border: 1px solid var(--pd-border); border-radius: 10px; padding: 12px 14px 12px 46px; background: var(--pd-soft); min-height: 74px; }
    .pd-dot { position: absolute; left: 14px; top: 13px; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center;
              justify-content: center; font-size: 12px; font-weight: 800; color: #fff; background: var(--pd-muted); }
    .pd-step.done .pd-dot { background: var(--pd-ok); }
    .pd-step.bad .pd-dot { background: var(--pd-bad); }
    .pd-step.current { border-color: var(--pd-warn); }
    .pd-step.current .pd-dot { background: var(--pd-warn); }
    .pd-step.skip, .pd-step.todo { opacity: .55; }
    .pd-step b { display: block; font-size: 14px; }
    .pd-step span { display: block; color: var(--pd-muted); font-size: 12px; margin-top: 3px; }
    .pd-wrap { overflow-x: auto; border: 1px solid var(--pd-border); border-radius: 10px; }
    .pd-table { width: 100%; min-width: 760px; border-collapse: separate; border-spacing: 0; }
    .pd-table th { background: var(--pd-head); color: var(--pd-muted); font-size: 12px; font-weight: 700; text-transform: uppercase;
                   letter-spacing: .03em; padding: 10px 12px; text-align: right; border-bottom: 1px solid var(--pd-border); white-space: nowrap; }
    .pd-table td { padding: 10px 12px; text-align: right; border-bottom: 1px solid var(--pd-border); font-variant-numeric: tabular-nums; vertical-align: middle; }
    .pd-table tbody tr:nth-child(even) td { background: var(--pd-soft); }
    .pd-table th:first-child, .pd-table td:first-child { text-align: left; }
    .pd-table tfoot td { font-weight: 800; background: var(--pd-head); border-bottom: 0; }
    .pd-name { font-weight: 700; }
    .pd-sub { display: block; color: var(--pd-muted); font-size: 12px; font-weight: 400; }
    .pd-strike { text-decoration: line-through; color: var(--pd-muted); font-weight: 400; margin-right: 4px; }
    .pd-row-off td { opacity: .6; }
    .pd-notes { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; }
    .pd-note { background: var(--pd-soft); border: 1px solid var(--pd-border); border-radius: 10px; padding: 12px 14px; }
    .pd-note small { display: block; color: var(--pd-muted); font-size: 12px; margin-bottom: 6px; font-weight: 600; }
    .pd-note p { margin: 0; white-space: pre-line; }
    .pd-empty { color: var(--pd-muted); font-style: italic; }
    .pd-photo img { max-height: 180px; border-radius: 8px; border: 1px solid var(--pd-border); display: block; }
    @media (max-width: 720px) { .pd-steps { grid-template-columns: 1fr; } }
</style>

<div class="pd">
    {{-- Ringkasan --}}
    <div class="pd-card">
        <div class="pd-head">
            <div>
                <div class="pd-no">{{ $p->procurement_number }}</div>
                <span class="pd-sub">Diajukan {{ $p->created_at?->format('d/m/Y H:i') }} oleh {{ $p->requester?->name ?? '-' }}</span>
            </div>
            <span class="pd-badge big {{ $statusClass }}">{{ $statusLabel }}</span>
        </div>
        <div class="pd-stats">
            <div class="pd-stat"><small>Jumlah barang</small><strong>{{ $num($items->count()) }}</strong></div>
            <div class="pd-stat"><small>Total diajukan</small><strong>{{ $rp($totalRequested) }}</strong></div>
            <div class="pd-stat">
                <small>Total disetujui (potong Kas Besar)</small>
                <strong>{{ $approvedLike ? $rp($p->total_approved_amount) : '–' }}</strong>
            </div>
            <div class="pd-stat">
                <small>Dihemat dari pengajuan</small>
                <strong>{{ $approvedLike ? $rp(max(0, $totalRequested - $p->total_approved_amount)) : '–' }}</strong>
            </div>
        </div>
    </div>

    {{-- Alur status --}}
    <div class="pd-card">
        <p class="pd-title">Alur Pengajuan</p>
        <div class="pd-steps">
            @foreach ($steps as $i => [$title, $at, $who, $state])
                <div class="pd-step {{ $state }}">
                    <div class="pd-dot">{{ $state === 'done' ? '✓' : ($state === 'bad' ? '✕' : $i + 1) }}</div>
                    <b>{{ $title }}</b>
                    @if ($at)
                        <span>{{ $at->format('d/m/Y H:i') }}{{ $who ? ' · oleh ' . $who : '' }}</span>
                    @elseif ($state === 'current')
                        <span>Sedang menunggu</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Barang --}}
    <div class="pd-card">
        <p class="pd-title">Barang yang Diajukan</p>
        <div class="pd-wrap">
            <table class="pd-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Stok saat diajukan</th>
                        <th>Diajukan</th>
                        <th>Disetujui</th>
                        <th>Harga satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        @php
                            $rejected = $approvedLike && !$item->is_approved;
                            $qtyDiff = $approvedLike && $item->is_approved ? (int) $item->quantity_approved - (int) $item->quantity_requested : 0;
                            $priceChanged = $approvedLike && $item->unit_cogs_approved !== null && (int) $item->unit_cogs_approved !== (int) $item->unit_cogs_requested;
                        @endphp
                        <tr class="{{ $rejected ? 'pd-row-off' : '' }}">
                            <td>
                                <span class="pd-name">{{ $item->product?->name ?? 'Produk terhapus' }}</span>
                                <span class="pd-sub">{{ $item->product?->unit }}</span>
                            </td>
                            <td>{{ $num($item->current_stock) }}</td>
                            <td>{{ $num($item->quantity_requested) }}</td>
                            <td>
                                @if (!$approvedLike)
                                    <span class="pd-sub">–</span>
                                @elseif ($rejected)
                                    <span class="pd-badge bad">Dicoret</span>
                                @else
                                    <b>{{ $num($item->quantity_approved) }}</b>
                                    @if ($qtyDiff !== 0)
                                        <span class="pd-badge {{ $qtyDiff < 0 ? 'warn' : 'info' }}">{{ $qtyDiff > 0 ? '+' : '−' }}{{ $num(abs($qtyDiff)) }}</span>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if ($priceChanged)
                                    <span class="pd-strike">{{ $rp($item->unit_cogs_requested) }}</span>{{ $rp($item->unit_cogs_approved) }}
                                @else
                                    {{ $rp($item->unit_cogs_requested) }}
                                @endif
                            </td>
                            <td>
                                @if ($approvedLike)
                                    <b>{{ $rejected ? '–' : $rp($item->subtotal_approved) }}</b>
                                    @if (!$rejected && (int) $item->subtotal_approved !== (int) $item->subtotal_requested)
                                        <span class="pd-sub">diajukan {{ $rp($item->subtotal_requested) }}</span>
                                    @endif
                                @else
                                    <b>{{ $rp($item->subtotal_requested) }}</b>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5">{{ $approvedLike ? 'Total disetujui' : 'Total diajukan' }}</td>
                        <td>{{ $rp($approvedLike ? $totalApproved : $totalRequested) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Catatan & bukti --}}
    <div class="pd-card">
        <p class="pd-title">Catatan &amp; Bukti</p>
        <div class="pd-notes">
            <div class="pd-note">
                <small>Catatan pengaju</small>
                @if (filled($p->notes)) <p>{{ $p->notes }}</p> @else <p class="pd-empty">Tidak ada catatan</p> @endif
            </div>
            <div class="pd-note">
                <small>Catatan owner</small>
                @if (filled($p->owner_notes)) <p>{{ $p->owner_notes }}</p> @else <p class="pd-empty">Tidak ada catatan</p> @endif
            </div>
            <div class="pd-note pd-photo">
                <small>Foto nota penerimaan</small>
                @if ($p->proof_image_url)
                    <a href="{{ $p->proof_image_url }}" target="_blank" rel="noopener"><img src="{{ $p->proof_image_url }}" alt="Nota penerimaan {{ $p->procurement_number }}"></a>
                @else
                    <p class="pd-empty">{{ match ($status) {
                        'RECEIVED' => 'Tidak ada foto nota',
                        'REJECTED' => 'Tidak ada (pengajuan ditolak)',
                        default => 'Belum ada (diisi saat barang diterima)',
                    } }}</p>
                @endif
            </div>
        </div>
    </div>
</div>
