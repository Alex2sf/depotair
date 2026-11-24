<x-filament-panels::page>
    @php
        $num = fn ($n) => number_format((int) $n, 0, ',', '.');
        $rows = $this->rows;
        $dailyRows = $this->dailyRows;
        $sum = $this->summary;
        $presets = ['today' => 'Hari ini', 'week' => '7 hari', 'month' => 'Bulan ini', 'lastmonth' => 'Bulan lalu'];
        $periodHeading = $selectedDay
            ? \Carbon\Carbon::parse($selectedDay)->translatedFormat('d M Y (l)')
            : (\Carbon\Carbon::parse($from ?: now())->translatedFormat('d M Y') . ' – ' . \Carbon\Carbon::parse($to ?: now())->translatedFormat('d M Y'));
    @endphp

    <style>
        .kk { --kk-border: var(--gray-200, #e5e7eb); --kk-muted: var(--gray-500, #6b7280); --kk-text: var(--gray-950, #030712);
              --kk-bg: #fff; --kk-zebra: var(--gray-50, #f9fafb); --kk-hover: var(--gray-100, #f3f4f6); --kk-head: var(--gray-100, #f3f4f6);
              --kk-in: var(--success-600, #16a34a); --kk-out: var(--danger-600, #dc2626); --kk-warn: var(--warning-600, #d97706); --kk-accent: var(--primary-600, #d97706); }
        .dark .kk { --kk-border: rgba(255,255,255,.1); --kk-muted: var(--gray-400, #9ca3af); --kk-text: #fff; --kk-bg: var(--gray-900, #111827);
                    --kk-zebra: rgba(255,255,255,.03); --kk-hover: rgba(255,255,255,.07); --kk-head: var(--gray-800, #1f2937);
                    --kk-in: var(--success-400, #4ade80); --kk-out: var(--danger-400, #f87171); --kk-warn: var(--warning-400, #fbbf24); --kk-accent: var(--primary-400, #fbbf24); }
        .kk, .kk * { box-sizing: border-box; }
        .kk { color: var(--kk-text); font-size: 14px; display: flex; flex-direction: column; gap: 16px; }
        .kk-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; }
        .kk-card { background: var(--kk-bg); border: 1px solid var(--kk-border); border-radius: 12px; padding: 14px 16px; }
        .kk-card small { display: block; color: var(--kk-muted); font-size: 12px; margin-bottom: 4px; }
        .kk-card strong { font-size: 24px; font-weight: 700; line-height: 1.1; }
        .kk-card.is-warn strong { color: var(--kk-warn); }
        .kk-card.is-bad strong { color: var(--kk-out); }
        .kk-bar { background: var(--kk-bg); border: 1px solid var(--kk-border); border-radius: 12px; padding: 12px 14px;
                  display: flex; flex-wrap: wrap; gap: 12px 16px; align-items: center; }
        .kk-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .kk-btn { border: 1px solid var(--kk-border); background: transparent; color: var(--kk-text); border-radius: 8px;
                  padding: 6px 12px; font-size: 13px; cursor: pointer; }
        .kk-btn:hover { background: var(--kk-hover); }
        .kk-btn.is-on { background: var(--kk-accent); border-color: var(--kk-accent); color: #fff; font-weight: 600; }
        .kk-field { display: flex; align-items: center; gap: 6px; font-size: 13px; color: var(--kk-muted); }
        .kk-input { border: 1px solid var(--kk-border); background: transparent; color: var(--kk-text); border-radius: 8px;
                    padding: 6px 10px; font-size: 13px; }
        .kk-input.search { min-width: 220px; }
        .kk-check { display: flex; align-items: center; gap: 6px; font-size: 13px; cursor: pointer; margin-left: auto; }
        .kk-wrap { background: var(--kk-bg); border: 1px solid var(--kk-border); border-radius: 12px; overflow: auto; max-height: 72vh; }
        .kk-table { border-collapse: separate; border-spacing: 0; width: 100%; min-width: 1060px; }
        .kk-table th { position: sticky; top: 0; z-index: 2; background: var(--kk-head); color: var(--kk-muted); font-size: 12px;
                       font-weight: 600; text-transform: uppercase; letter-spacing: .03em; padding: 10px 12px; text-align: right;
                       border-bottom: 1px solid var(--kk-border); white-space: nowrap; }
        .kk-table td { background: var(--kk-bg); padding: 9px 12px; text-align: right; border-bottom: 1px solid var(--kk-border);
                       font-variant-numeric: tabular-nums; vertical-align: middle; }
        .kk-table tbody tr:nth-child(even) td { background: var(--kk-zebra); }
        .kk-table tbody tr:hover td { background: var(--kk-hover); }
        .kk-table th:first-child, .kk-table td:first-child { text-align: left; position: sticky; left: 0; min-width: 240px; }
        .kk-table th:first-child { z-index: 3; }
        .kk-table td:first-child { z-index: 1; }
        .kk-table .sep { border-left: 1px solid var(--kk-border); }
        .kk-name { font-weight: 600; }
        .kk-sub { display: block; color: var(--kk-muted); font-size: 12px; font-weight: 400; margin-top: 1px; }
        .kk-zero { color: var(--kk-muted); opacity: .6; }
        .kk-in { color: var(--kk-in); font-weight: 600; }
        .kk-out { color: var(--kk-out); font-weight: 600; }
        .kk-end { font-weight: 700; font-size: 15px; }
        .kk-end.neg { color: var(--kk-out); }
        .kk-badge { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 12px; font-weight: 600; white-space: nowrap;
                    background: color-mix(in srgb, var(--kk-muted) 18%, transparent); color: var(--kk-muted); }
        .kk-badge.ok { background: color-mix(in srgb, var(--kk-in) 16%, transparent); color: var(--kk-in); }
        .kk-badge.warn { background: color-mix(in srgb, var(--kk-warn) 18%, transparent); color: var(--kk-warn); }
        .kk-badge.bad { background: color-mix(in srgb, var(--kk-out) 16%, transparent); color: var(--kk-out); }
        .kk-help { color: var(--kk-muted); font-size: 12px; line-height: 1.6; }
        .kk-empty { text-align: center !important; color: var(--kk-muted); padding: 32px !important; }
        .kk-panel { background: var(--kk-bg); border: 1px solid var(--kk-border); border-radius: 12px; overflow: hidden; }
        .kk-panel-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 10px;
                         padding: 12px 16px; border-bottom: 1px solid var(--kk-border); background: var(--kk-head); }
        .kk-panel-title { font-weight: 700; font-size: 14px; }
        .kk-daily-wrap { overflow: auto; max-height: 310px; }
        .kk-daily-table { border-collapse: separate; border-spacing: 0; width: 100%; min-width: 720px; }
        .kk-daily-table th { position: sticky; top: 0; z-index: 2; background: var(--kk-head); color: var(--kk-muted); font-size: 12px;
                             font-weight: 600; text-transform: uppercase; letter-spacing: .03em; padding: 9px 12px; text-align: right;
                             border-bottom: 1px solid var(--kk-border); white-space: nowrap; }
        .kk-daily-table td { padding: 8px 12px; text-align: right; border-bottom: 1px solid var(--kk-border); font-variant-numeric: tabular-nums; }
        .kk-daily-table th:first-child, .kk-daily-table td:first-child { text-align: left; }
        .kk-daily-table tbody tr { cursor: pointer; }
        .kk-daily-table tbody tr:hover td { background: var(--kk-hover); }
        .kk-daily-table tbody tr.is-active td { background: color-mix(in srgb, var(--kk-accent) 12%, transparent); font-weight: 600; }
        .kk-detail-title { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    </style>

    <div class="kk">
        <div class="kk-cards">
            <div class="kk-card"><small>Jumlah produk</small><strong>{{ $num($sum['products']) }}</strong></div>
            <div class="kk-card"><small>Total stok sistem (sekarang)</small><strong>{{ $num($sum['stock']) }}</strong></div>
            <div class="kk-card {{ $sum['history_gap'] > 0 ? 'is-warn' : '' }}">
                <small>Riwayat tidak cocok dengan stok sistem</small><strong>{{ $num($sum['history_gap']) }}</strong>
            </div>
            <div class="kk-card {{ $sum['opname_issue'] > 0 ? 'is-bad' : '' }}">
                <small>Selisih opname terakhir</small><strong>{{ $num($sum['opname_issue']) }}</strong>
            </div>
        </div>

        <div class="kk-panel">
            <div class="kk-panel-head">
                <span class="kk-panel-title">Rekap Harian 1 Bulan (Klik tanggal untuk lihat detail semua barang)</span>
                <label class="kk-field">
                    Pilih Bulan
                    <input type="month" class="kk-input" wire:model.live="selectedMonth">
                </label>
            </div>
            <div class="kk-daily-wrap">
                <table class="kk-daily-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>+ Restock</th>
                            <th>− Jual</th>
                            <th>Produk Bergerak</th>
                            <th>Di-Opname</th>
                            <th>Status Selisih</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dailyRows as $day)
                            <tr
                                wire:key="kk-day-{{ $day['date'] }}"
                                wire:click="selectDay('{{ $day['date'] }}')"
                                class="{{ $selectedDay === $day['date'] ? 'is-active' : '' }}"
                            >
                                <td><strong>{{ $day['label'] }}</strong></td>
                                <td>@if ($day['restock']) <span class="kk-in">+{{ $num($day['restock']) }}</span> @else <span class="kk-zero">–</span> @endif</td>
                                <td>@if ($day['sale']) <span class="kk-out">−{{ $num($day['sale']) }}</span> @else <span class="kk-zero">–</span> @endif</td>
                                <td>{{ $num($day['moved_products']) }} produk</td>
                                <td>{{ $day['opname_count'] > 0 ? $num($day['opname_count']) . ' produk' : '–' }}</td>
                                <td>
                                    @if ($day['opname_count'] === 0)
                                        <span class="kk-badge">Belum Opname</span>
                                    @elseif ($day['opname_issues'] === 0)
                                        <span class="kk-badge ok">✓ Aman (0 selisih)</span>
                                    @else
                                        <span class="kk-badge bad">{{ $num($day['opname_issues']) }} Barang Selisih</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" wire:click.stop="selectDay('{{ $day['date'] }}')" class="kk-btn {{ $selectedDay === $day['date'] ? 'is-on' : '' }}">
                                        {{ $selectedDay === $day['date'] ? 'Sedang Dibuka' : 'Lihat Detail' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="kk-empty">Belum ada data pada bulan yang dipilih.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="kk-bar">
            <div class="kk-detail-title">Detail Semua Barang: <span class="kk-badge warn">{{ $periodHeading }}</span></div>
            <div class="kk-group">
                @foreach ($presets as $key => $label)
                    <button type="button" wire:click="setPreset('{{ $key }}')" class="kk-btn {{ $preset === $key ? 'is-on' : '' }}">{{ $label }}</button>
                @endforeach
            </div>
            <label class="kk-field">Dari <input type="date" class="kk-input" wire:model.live="from"></label>
            <label class="kk-field">Sampai <input type="date" class="kk-input" wire:model.live="to"></label>
            <input type="search" class="kk-input search" placeholder="Cari produk..." wire:model.live.debounce.300ms="search">
            <label class="kk-check"><input type="checkbox" wire:model.live="onlyIssues"> Hanya yang bermasalah</label>
        </div>

        <div class="kk-wrap">
            <table class="kk-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Stok awal</th>
                        <th>+ Restock</th>
                        <th>+ Retur</th>
                        <th>− Jual</th>
                        <th>− Rusak</th>
                        <th>± Sesuai</th>
                        <th class="sep">Stok akhir</th>
                        <th>Stok sistem</th>
                        <th>Cek riwayat</th>
                        <th class="sep">Opname terakhir</th>
                        <th>Selisih opname</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr wire:key="kk-{{ $row['product_id'] }}">
                            <td>
                                <span class="kk-name">{{ $row['product'] }}</span>
                                <span class="kk-sub">{{ $row['unit'] }}</span>
                            </td>
                            <td>{{ $num($row['opening']) }}</td>
                            <td>@if ($row['restock']) <span class="kk-in">+{{ $num($row['restock']) }}</span> @else <span class="kk-zero">–</span> @endif</td>
                            <td>@if ($row['return']) <span class="kk-in">+{{ $num($row['return']) }}</span> @else <span class="kk-zero">–</span> @endif</td>
                            <td>@if ($row['sale']) <span class="kk-out">−{{ $num($row['sale']) }}</span> @else <span class="kk-zero">–</span> @endif</td>
                            <td>@if ($row['damage']) <span class="kk-out">−{{ $num($row['damage']) }}</span> @else <span class="kk-zero">–</span> @endif</td>
                            <td>
                                @if ($row['adjustment'] > 0) <span class="kk-in">+{{ $num($row['adjustment']) }}</span>
                                @elseif ($row['adjustment'] < 0) <span class="kk-out">−{{ $num(abs($row['adjustment'])) }}</span>
                                @else <span class="kk-zero">–</span> @endif
                            </td>
                            <td class="sep"><span class="kk-end {{ $row['closing'] < 0 ? 'neg' : '' }}">{{ $num($row['closing']) }}</span></td>
                            <td>{{ $num($row['current']) }}</td>
                            <td>
                                @if ($row['history_gap'] === 0)
                                    <span class="kk-badge ok">✓ Cocok</span>
                                @else
                                    <span class="kk-badge warn" title="Stok sistem {{ $num($row['current']) }}, hasil hitung riwayat {{ $num($row['closing']) }}. Ada perubahan stok yang tidak tercatat di riwayat.">
                                        Riwayat {{ $row['history_gap'] > 0 ? 'kurang' : 'lebih' }} {{ $num(abs($row['history_gap'])) }}
                                    </span>
                                @endif
                            </td>
                            <td class="sep">
                                @if ($row['opname_at'])
                                    <strong>{{ $num($row['opname_real']) }}</strong>
                                    <span class="kk-sub">{{ $row['opname_at']->format('d/m H:i') }} · sistem {{ $num($row['opname_system']) }}</span>
                                @else
                                    <span class="kk-zero">–</span>
                                @endif
                            </td>
                            <td>
                                @if ($row['opname_at'] === null)
                                    <span class="kk-zero">–</span>
                                @elseif ((int) $row['opname_difference'] === 0)
                                    <span class="kk-badge ok">Pas</span>
                                @else
                                    <span class="kk-badge {{ $row['opname_difference'] < 0 ? 'bad' : 'warn' }}">
                                        {{ $row['opname_difference'] > 0 ? '+' : '−' }}{{ $num(abs($row['opname_difference'])) }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="12" class="kk-empty">Tidak ada produk yang cocok dengan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="kk-help">
            Stok akhir = stok awal + restock + retur − jual − rusak ± penyesuaian, dihitung dari riwayat gerakan stok sampai tanggal akhir.
            <b>Cek riwayat</b> membandingkan stok akhir itu dengan stok sistem sekarang (hanya kalau periode berakhir hari ini).
            Selisih opname = hitungan fisik − stok sistem saat dihitung. Halaman ini dihitung ulang setiap dibuka atau saat filter diubah.
        </p>
    </div>
</x-filament-panels::page>
