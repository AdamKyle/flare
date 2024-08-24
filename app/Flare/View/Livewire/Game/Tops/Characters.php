<?php

namespace App\Flare\View\Livewire\Game\Tops;

use App\Flare\Models\Character;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class Characters extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Character::orderBy('level', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')->hideIf(true),
            Column::make('Name', 'name')->searchable()->format(
                fn ($value, $row, Column $column) => '<a href="/game/tops/'.$row->id.'">'.$value.'</a>'
            )->html(),
            Column::make('Level', 'level')->searchable()->format(
                fn ($value, $row, Column $column) => number_format($value)
            )->html(),
            Column::make('Gold', 'gold')->searchable()->format(
                fn ($value, $row, Column $column) => number_format($value)
            )->html(),
        ];
    }
}
