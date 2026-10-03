<?php

namespace App\Providers;

use Filament\Support\Enums\Width;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Filament\Support\Facades\FilamentAsset::register([
            \Filament\Support\Assets\Css::make('flatpickr-css', asset('vendor/flatpickr/flatpickr.min.css')),
            \Filament\Support\Assets\Js::make('flatpickr-js', asset('vendor/flatpickr/flatpickr.min.js')),
            \Filament\Support\Assets\Js::make('flatpickr-id', asset('vendor/flatpickr/l10n/id.js')),
        ]);

        Table::configureUsing(function (Table $table): void {
            $table
                ->filtersLayout(FiltersLayout::Modal)
                ->filtersFormColumns(2)
                ->filtersFormWidth(Width::Large);
        });
    }
}
