<?php

namespace App\Filament\Pages;

use App\Support\StockCard;
use Carbon\Carbon;
use Filament\Pages\Page;

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

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
    }

    public function getRowsProperty(): array
    {
        $from = Carbon::parse($this->from ?: now()->startOfMonth());
        $to = Carbon::parse($this->to ?: now());
        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return StockCard::rows($from, $to);
    }
}
