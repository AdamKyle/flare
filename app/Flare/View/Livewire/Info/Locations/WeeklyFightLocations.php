<?php

namespace App\Flare\View\Livewire\Info\Locations;

use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationType;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class WeeklyFightLocations extends DataTableComponent
{
    private array $weeklyFightLocations = [
        LocationType::LORDS_STRONG_HOLD,
        LocationType::BROKEN_ANVIL,
        LocationType::TWSITED_MAIDENS_DUNGEONS,
        LocationType::ALCHEMY_CHURCH,
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Location::whereIn('type', $this->weeklyFightLocations);
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $locationId = Location::where('name', $value)->first()->id;

                return '<a href="/information/locations/' . $locationId . '" >' . $row->name . '</a>';
            })->html(),

            Column::make('Game Map', 'game_map_id')->searchable()->sortable()->format(function ($value, $row) {
                $gameMap = GameMap::find($value);

                return '<span>' . $gameMap->name . ($gameMap->only_during_event_type ? ' <i class="fas fa-star text-yellow-700 dark:text-yellow-500"></i> ' : '') . '</span>';
            })->html(),
            Column::make('X Coordinate', 'x')->sortable(),
            Column::make('Y Coordinate', 'y')->sortable(),
        ];
    }
}
