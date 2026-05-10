<?php

namespace App\Filament\Filters;

use Filament\Tables\Filters\Filter;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PeriodeFilter
{
    public static function make(string $name = 'periode', string $column = 'created_at', string $label = 'Periode'): Filter
    {
        return Filter::make($name)
            ->label($label)
            ->form([
                Radio::make('jenis_periode')
                    ->label('Jenis Periode:')
                    ->options([
                        'Tahun' => 'Tahun',
                        'Bulan' => 'Bulan',
                        'Tanggal' => 'Tanggal',
                    ])
                    ->inline()
                    ->inlineLabel(false)
                    ->default('Bulan')
                    ->live(),
                    
                Group::make([
                    Select::make('tahun')
                        ->label('Tahun')
                        ->options(function () {
                            $years = range(date('Y') - 5, date('Y') + 1);
                            return array_combine($years, $years);
                        })
                        ->default(date('Y'))
                        ->searchable(),
                ])->visible(fn (Get $get) => $get('jenis_periode') === 'Tahun'),
                
                Group::make([
                    DatePicker::make('bulan')
                        ->label('Bulan')
                        ->format('Y-m') // Value = YYYY-MM
                        ->displayFormat('F Y') // Label = March 2026
                        ->native(false)
                        ->default(now()->format('Y-m')),
                ])->visible(fn (Get $get) => $get('jenis_periode') === 'Bulan'),
                
                Group::make([
                    DatePicker::make('dari_tanggal')
                        ->label('Dari Tanggal')
                        ->native(false),
                    DatePicker::make('sampai_tanggal')
                        ->label('Sampai Tanggal')
                        ->native(false),
                ])->visible(fn (Get $get) => $get('jenis_periode') === 'Tanggal')->columns(2),
            ])
            ->query(function (Builder $query, array $data) use ($column) {
                if (($data['jenis_periode'] ?? '') === 'Tahun' && !empty($data['tahun'])) {
                    return $query->whereYear($column, $data['tahun']);
                }
                
                if (($data['jenis_periode'] ?? '') === 'Bulan' && !empty($data['bulan'])) {
                    $month = substr($data['bulan'], 5, 2);
                    $year = substr($data['bulan'], 0, 4);
                    return $query->whereMonth($column, $month)->whereYear($column, $year);
                }
                
                if (($data['jenis_periode'] ?? '') === 'Tanggal') {
                    return $query
                        ->when($data['dari_tanggal'], fn ($q, $date) => $q->whereDate($column, '>=', $date))
                        ->when($data['sampai_tanggal'], fn ($q, $date) => $q->whereDate($column, '<=', $date));
                }
                
                return $query;
            })
            ->indicateUsing(function (array $data): ?string {
                if (($data['jenis_periode'] ?? '') === 'Tahun' && !empty($data['tahun'])) {
                    return 'Tahun: ' . $data['tahun'];
                }
                if (($data['jenis_periode'] ?? '') === 'Bulan' && !empty($data['bulan'])) {
                    return 'Bulan: ' . Carbon::parse($data['bulan'] . '-01')->translatedFormat('F Y');
                }
                if (($data['jenis_periode'] ?? '') === 'Tanggal') {
                    if (empty($data['dari_tanggal']) && empty($data['sampai_tanggal'])) return null;
                    return 'Tanggal: ' . 
                        (!empty($data['dari_tanggal']) ? Carbon::parse($data['dari_tanggal'])->format('d/m/Y') : '...') . 
                        ' - ' . 
                        (!empty($data['sampai_tanggal']) ? Carbon::parse($data['sampai_tanggal'])->format('d/m/Y') : '...');
                }
                return null;
            });
    }
}
