<?php

namespace App\Filament\Filters;

use App\Filament\Forms\Components\DateRangePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PeriodeFilter
{
    public static function make(string $name = 'periode', string $column = 'created_at', string $label = 'Periode'): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->form([
                ToggleButtons::make('jenis_filter')
                    ->label('Pilih Filter Berdasarkan:')
                    ->options([
                        'bulan' => 'Bulan',
                        'tahun' => 'Tahun',
                        'rentang' => 'Rentang Tanggal',
                    ])
                    ->icons([
                        'bulan' => 'heroicon-m-calendar-days',
                        'tahun' => 'heroicon-m-calendar',
                        'rentang' => 'heroicon-m-clock',
                    ])
                    ->default('bulan')
                    ->inline()
                    ->live()
                    ->columnSpanFull(),

                Select::make('bulan')
                    ->label('Pilih Bulan')
                    ->options([
                        '01' => 'Januari',
                        '02' => 'Februari',
                        '03' => 'Maret',
                        '04' => 'April',
                        '05' => 'Mei',
                        '06' => 'Juni',
                        '07' => 'Juli',
                        '08' => 'Agustus',
                        '09' => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->default(now()->format('m'))
                    ->visible(fn (Get $get) => ($get('jenis_filter') ?? 'bulan') === 'bulan'),

                Select::make('tahun_bulan')
                    ->label('Tahun')
                    ->options(function () {
                        $years = range(date('Y') - 5, date('Y') + 1);
                        return array_combine($years, $years);
                    })
                    ->default(date('Y'))
                    ->visible(fn (Get $get) => ($get('jenis_filter') ?? 'bulan') === 'bulan'),

                Select::make('tahun')
                    ->label('Pilih Tahun')
                    ->options(function () {
                        $years = range(date('Y') - 5, date('Y') + 1);
                        return array_combine($years, $years);
                    })
                    ->default(date('Y'))
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('jenis_filter') === 'tahun'),

                DateRangePicker::make('rentang_tanggal')
                    ->label('Rentang Waktu (Pilih Tanggal Mulai s/d Selesai)')
                    ->placeholder('Klik di sini untuk memilih rentang tanggal...')
                    ->columnSpanFull()
                    ->visible(fn (Get $get) => $get('jenis_filter') === 'rentang'),
            ])
            ->query(function (Builder $query, array $data) use ($column) {
                $jenis = $data['jenis_filter'] ?? 'bulan';

                if ($jenis === 'rentang' && !empty($data['rentang_tanggal'])) {
                    $dates = explode(' to ', $data['rentang_tanggal']);
                    $start = trim($dates[0]);
                    $end = trim($dates[1] ?? $dates[0]);

                    return $query->whereDate($column, '>=', $start)
                                 ->whereDate($column, '<=', $end);
                }

                if ($jenis === 'tahun' && !empty($data['tahun'])) {
                    return $query->whereYear($column, $data['tahun']);
                }

                if ($jenis === 'bulan' && !empty($data['bulan'])) {
                    $year = $data['tahun_bulan'] ?? date('Y');
                    return $query->whereMonth($column, $data['bulan'])
                                 ->whereYear($column, $year);
                }

                // Fallback untuk backward compatibility
                if (!empty($data['dari_tanggal']) || !empty($data['sampai_tanggal'])) {
                    return $query
                        ->when($data['dari_tanggal'] ?? null, fn ($q, $date) => $q->whereDate($column, '>=', $date))
                        ->when($data['sampai_tanggal'] ?? null, fn ($q, $date) => $q->whereDate($column, '<=', $date));
                }

                return $query;
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                $jenis = $data['jenis_filter'] ?? null;

                if ($jenis === 'rentang' && !empty($data['rentang_tanggal'])) {
                    $dates = explode(' to ', $data['rentang_tanggal']);
                    $start = trim($dates[0]);
                    $end = trim($dates[1] ?? $dates[0]);
                    $indicators[] = 'Rentang: ' . Carbon::parse($start)->format('d/m/Y') . ' s/d ' . Carbon::parse($end)->format('d/m/Y');
                } elseif ($jenis === 'tahun' && !empty($data['tahun'])) {
                    $indicators[] = 'Tahun: ' . $data['tahun'];
                } elseif ($jenis === 'bulan' && !empty($data['bulan'])) {
                    $monthName = Carbon::createFromDate(null, (int) $data['bulan'], 1)->translatedFormat('F');
                    $year = $data['tahun_bulan'] ?? date('Y');
                    $indicators[] = "Bulan: {$monthName} {$year}";
                } elseif (!empty($data['dari_tanggal']) && !empty($data['sampai_tanggal'])) {
                    $indicators[] = 'Periode: ' . Carbon::parse($data['dari_tanggal'])->format('d/m/Y') . ' s/d ' . Carbon::parse($data['sampai_tanggal'])->format('d/m/Y');
                }

                return $indicators;
            });
    }
}
