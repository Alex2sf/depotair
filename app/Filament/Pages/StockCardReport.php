<?php

namespace App\Filament\Pages;

use App\Support\StockCard;
use Carbon\Carbon;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;

class StockCardReport extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected string $view = 'filament.pages.stock-card-report';
    protected static ?string $navigationLabel = 'Kartu Stok & Opname';
    protected static string | \UnitEnum | null $navigationGroup = 'Inventori';
    protected static ?string $title = 'Kartu Stok & Selisih Opname';
    protected static ?string $slug = 'kartu-stok';

    public string $from = '';
    public string $to = '';
    public string $preset = 'month';
    public string $selectedMonth = '';
    public ?string $selectedDay = null;
    public string $search = '';
    public bool $onlyIssues = false;

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->setPreset('month');
    }

    public function setPreset(string $preset): void
    {
        $today = now();
        [$from, $to] = match ($preset) {
            'today' => [$today->copy(), $today->copy()],
            'week' => [$today->copy()->subDays(6), $today->copy()],
            'lastmonth' => [$today->copy()->subMonthNoOverflow()->startOfMonth(), $today->copy()->subMonthNoOverflow()->endOfMonth()],
            default => [$today->copy()->startOfMonth(), $today->copy()],
        };

        $this->preset = in_array($preset, ['today', 'week', 'lastmonth'], true) ? $preset : 'month';
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
        $this->selectedMonth = $from->format('Y-m');
        $this->selectedDay = $preset === 'today' ? $today->toDateString() : null;
    }

    public function updatedSelectedMonth(): void
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $this->selectedMonth)) {
            $this->selectedMonth = now()->format('Y-m');
        }

        [$year, $month] = array_map('intval', explode('-', $this->selectedMonth));
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();
        if ($end->gt(now())) {
            $end = now();
        }

        $this->from = $start->toDateString();
        $this->to = $end->toDateString();
        $this->preset = '';
        $this->selectedDay = null;
    }

    public function selectDay(string $date): void
    {
        $day = Carbon::parse($date);
        $this->from = $day->toDateString();
        $this->to = $day->toDateString();
        $this->selectedDay = $day->toDateString();
        $this->selectedMonth = $day->format('Y-m');
        $this->preset = $day->isToday() ? 'today' : '';
    }

    // Tanggal diubah manual: tombol rentang cepat tidak lagi dianggap aktif
    public function updatedFrom(): void
    {
        $this->preset = '';
        $this->selectedDay = ($this->from !== '' && $this->from === $this->to) ? $this->from : null;
    }

    public function updatedTo(): void
    {
        $this->preset = '';
        $this->selectedDay = ($this->from !== '' && $this->from === $this->to) ? $this->from : null;
    }

    #[Computed]
    public function dailyRows(): array
    {
        $ym = preg_match('/^\d{4}-\d{2}$/', $this->selectedMonth) ? $this->selectedMonth : now()->format('Y-m');
        [$year, $month] = array_map('intval', explode('-', $ym));

        return StockCard::monthlyDailySummary($year, $month);
    }

    /** Semua produk untuk periode terpilih, lengkap dengan penanda masalah. Dihitung sekali per request. */
    #[Computed]
    public function baseRows(): array
    {
        $from = Carbon::parse($this->from ?: now()->startOfMonth());
        $to = Carbon::parse($this->to ?: now());
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        // Perbandingan riwayat vs stok sistem hanya bermakna kalau periode berakhir hari ini (atau lebih)
        $comparable = $to->copy()->startOfDay()->gte(now()->startOfDay());

        return array_map(function (array $row) use ($comparable) {
            $row['history_gap'] = $comparable ? ((int) $row['current'] - (int) $row['closing']) : 0;
            $row['opname_issue'] = $row['opname_at'] !== null && (int) $row['opname_difference'] !== 0;
            $row['has_issue'] = $row['history_gap'] !== 0 || $row['opname_issue'] || (int) $row['closing'] < 0;

            return $row;
        }, StockCard::rows($from, $to));
    }

    #[Computed]
    public function rows(): array
    {
        $needle = mb_strtolower(trim($this->search));

        $filtered = array_values(array_filter($this->baseRows, function (array $row) use ($needle) {
            if ($needle !== '' && !str_contains(mb_strtolower($row['product']), $needle)) {
                return false;
            }

            return !$this->onlyIssues || $row['has_issue'];
        }));

        // Urutkan barang yang ada selisih/masalah paling atas agar langsung terlihat
        usort($filtered, function (array $a, array $b) {
            if ($a['has_issue'] !== $b['has_issue']) {
                return $a['has_issue'] ? -1 : 1;
            }

            return strcasecmp($a['product'], $b['product']);
        });

        return $filtered;
    }

    #[Computed]
    public function summary(): array
    {
        $rows = $this->baseRows;

        return [
            'products' => count($rows),
            'stock' => array_sum(array_column($rows, 'current')),
            'history_gap' => count(array_filter($rows, fn ($r) => $r['history_gap'] !== 0)),
            'opname_issue' => count(array_filter($rows, fn ($r) => $r['opname_issue'])),
        ];
    }
}
