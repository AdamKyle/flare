<?php

namespace App\Flare\View\Livewire\Info\Locations;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SpecialLocations extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Location::whereNotNull('enemy_strength_type');
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $locationId = Location::where('name', $value)->first()->id;

                return '<a href="/information/locations/'.$locationId.'" >'.$row->name.'</a>';
            })->html(),

            Column::make('Game Map', 'game_map_id')->searchable()->sortable()->format(function ($value, $row) {
                $gameMap = GameMap::find($value);

                return $gameMap->name;
            })->html(),
            Column::make('X Coordinate', 'x')->sortable(),
            Column::make('Y Coordinate', 'y')->sortable(),
        ];
    }
}
