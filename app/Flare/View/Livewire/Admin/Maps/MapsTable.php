<?php

namespace App\Flare\View\Livewire\Admin\Maps;

use App\Flare\Models\GameMap;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MapsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return GameMap::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $gameMapId = GameMap::where('name', $value)->first()->id;

                if (! is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/maps/'.$gameMapId.'">'.$row->name.'</a>';
                    }
                }

                return '<a href="/information/map/'.$gameMapId.'" >'.$row->name.'</a>';
            })->html(),
            Column::make('Default Map?', 'default')->searchable()->sortable()->format(function ($value, $row) {
                return $value ? 'Yes' : 'No';
            })->html(),
        ];
    }
}
