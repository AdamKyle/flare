<?php

namespace App\Flare\View\Livewire\Admin\Locations;

use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class LocationsTable extends DataTableComponent {

    public array $locationIds = [];

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {

        if (!empty($this->locationIds)) {
            return Location::whereIn('id', $this->locationIds);
        }

        return Location::query();
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $locationId = Location::where('name', $value)->first()->id;

                if (is_null(auth()->user())) {
                    return '<a href="/information/locations/'. $locationId.'" >'.$row->name . '</a>';
                }

                if (auth()->user()->hasRole('Admin')) {
                    return '<a href="/admin/location/'. $locationId.'">'.$row->name . '</a>';
                }

                return '<a href="/information/locations/'. $locationId.'" >'.$row->name . '</a>';
            })->html(),

            Column::make('Game Map', 'game_map_id')->searchable()->sortable()->format(function ($value, $row) {
                $gameMap = GameMap::find($value);

                return $gameMap->name;
            })->html(),
            Column::make('X Coordinate', 'x')->sortable(),
            Column::make('Y Coordinate', 'y')->sortable()
        ];
    }
}
