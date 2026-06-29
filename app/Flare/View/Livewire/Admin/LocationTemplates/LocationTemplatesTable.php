<?php

namespace App\Flare\View\Livewire\Admin\LocationTemplates;

use App\Flare\Models\LocationTemplate;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class LocationTemplatesTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return LocationTemplate::query();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->hideIf(true),
            Column::make('Name', 'name')
                ->searchable()
                ->sortable()
                ->format(function ($value, $row) {
                    return '<a href="'.route('admin.location-templates.show', ['locationTemplate' => $row->getRouteKey()]).'">'.e($row->name).'</a>';
                })
                ->html(),
            Column::make('Type', 'type')->searchable()->sortable(),
            Column::make('Port', 'is_port')
                ->format(fn ($value) => $value ? 'Yes' : 'No'),
            Column::make('Can Enter', 'can_players_enter')
                ->format(fn ($value) => $value ? 'Yes' : 'No'),
            Column::make('Actions')
                ->label(fn ($row) => '<a href="'.route('admin.location-templates.edit', ['locationTemplate' => $row->getRouteKey()]).'">Edit</a>')
                ->html(),
        ];
    }
}
