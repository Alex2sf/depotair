<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class DateRangePicker extends Field
{
    protected string $view = 'filament.forms.components.date-range-picker';

    protected ?string $placeholder = 'Klik untuk pilih rentang tanggal...';

    public function placeholder(?string $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }
}
