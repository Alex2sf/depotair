<?php

namespace App\Filament\Filters;

use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PeriodeFilter
{
    public static function make(string $name = 'periode', string $column = 'created_at', string $label = 'Periode'): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->form([
                Select::make('preset')
                    ->label('Pilihan Cepat (Opsional)')
                    ->placeholder('Pilih rentang cepat atau tentukan tanggal di bawah...')
                    ->options([
                        'today' => 'Hari Ini',
                        'yesterday' => 'Kemarin',
                        'last_7_days' => '7 Hari Terakhir',
                        'last_30_days' => '30 Hari Terakhir',
                        'this_month' => 'Bulan Ini (' . now()->translatedFormat('F Y') . ')',
                        'last_month' => 'Bulan Lalu (' . now()->subMonth()->translatedFormat('F Y') . ')',
                        'this_year' => 'Tahun Ini (' . now()->format('Y') . ')',
                    ])
                    ->live()
                    ->columnSpanFull()
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state === 'today') {
                            $set('dari_tanggal', now()->toDateString());
                            $set('sampai_tanggal', now()->toDateString());
                        } elseif ($state === 'yesterday') {
                            $set('dari_tanggal', now()->subDay()->toDateString());
                            $set('sampai_tanggal', now()->subDay()->toDateString());
                        } elseif ($state === 'last_7_days') {
                            $set('dari_tanggal', now()->subDays(6)->toDateString());
                            $set('sampai_tanggal', now()->toDateString());
                        } elseif ($state === 'last_30_days') {
                            $set('dari_tanggal', now()->subDays(29)->toDateString());
                            $set('sampai_tanggal', now()->toDateString());
                        } elseif ($state === 'this_month') {
                            $set('dari_tanggal', now()->startOfMonth()->toDateString());
                            $set('sampai_tanggal', now()->endOfMonth()->toDateString());
                        } elseif ($state === 'last_month') {
                            $set('dari_tanggal', now()->subMonth()->startOfMonth()->toDateString());
                            $set('sampai_tanggal', now()->subMonth()->endOfMonth()->toDateString());
                        } elseif ($state === 'this_year') {
                            $set('dari_tanggal', now()->startOfYear()->toDateString());
                            $set('sampai_tanggal', now()->endOfYear()->toDateString());
                        }
                    }),

                DatePicker::make('dari_tanggal')
                    ->label('Dari Tanggal')
                    ->placeholder('dd/mm/yyyy')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->closeOnDateSelection(),

                DatePicker::make('sampai_tanggal')
                    ->label('Sampai Tanggal')
                    ->placeholder('dd/mm/yyyy')
                    ->displayFormat('d/m/Y')
                    ->native(false)
                    ->closeOnDateSelection(),
            ])
            ->query(function (Builder $query, array $data) use ($column) {
                if (!empty($data['dari_tanggal']) || !empty($data['sampai_tanggal'])) {
                    return $query
                        ->when($data['dari_tanggal'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date))
                        ->when($data['sampai_tanggal'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));
                }

                // Fallback kompatibilitas jika ada parameter lawas (Tahun / Bulan)
                if (($data['jenis_periode'] ?? '') === 'Tahun' && !empty($data['tahun'])) {
                    return $query->whereYear($column, $data['tahun']);
                }

                if (($data['jenis_periode'] ?? '') === 'Bulan' && !empty($data['bulan'])) {
                    $month = substr($data['bulan'], 5, 2);
                    $year = substr($data['bulan'], 0, 4);
                    return $query->whereMonth($column, $month)->whereYear($column, $year);
                }

                return $query;
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if (!empty($data['dari_tanggal']) && !empty($data['sampai_tanggal'])) {
                    $indicators[] = 'Periode: ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y') . ' - ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                } elseif (!empty($data['dari_tanggal'])) {
                    $indicators[] = 'Dari: ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y');
                } elseif (!empty($data['sampai_tanggal'])) {
                    $indicators[] = 'Sampai: ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                } elseif (!empty($data['tahun']) && ($data['jenis_periode'] ?? '') === 'Tahun') {
                    $indicators[] = 'Tahun: ' . $data['tahun'];
                } elseif (!empty($data['bulan']) && ($data['jenis_periode'] ?? '') === 'Bulan') {
                    $indicators[] = 'Bulan: ' . Carbon::parse($data['bulan'] . '-01')->translatedFormat('F Y');
                }

                return $indicators;
            });
    }
}
