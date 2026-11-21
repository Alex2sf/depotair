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
    public string $search = '';
    public bool $onlyIssues = false;

    public function mount(): void
    {
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
    }

    // Tanggal diubah manual: tombol rentang cepat tidak lagi dianggap aktif
    public function updatedFrom(): void
    {
        $this->preset = '';
    }

    public function updatedTo(): void
    {
        $this->preset = '';
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

        return array_values(array_filter($this->baseRows, function (array $row) use ($needle) {
            if ($needle !== '' && !str_contains(mb_strtolower($row['product']), $needle)) {
                return false;
            }

            return !$this->onlyIssues || $row['has_issue'];
        }));
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
