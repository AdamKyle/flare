<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Units;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UnitsTable extends DataTableComponent
{
    public ?int $buildingId = null;

    public function mount(mixed $building = null): void
    {
        $this->buildingId = match (true) {
            $building instanceof GameBuilding => $building->id,
            is_array($building) => isset($building['id']) ? (int) $building['id'] : null,
            is_numeric($building) => (int) $building,
            default => null,
        };
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        if (is_null($this->buildingId) || ! GameBuilding::whereKey($this->buildingId)->exists()) {
            return GameUnit::query()->whereRaw('1 = 0');
        }

        return GameUnit::join('game_building_units', function ($join) {
            $join->on('game_building_units.game_unit_id', '=', 'game_units.id')
                ->where('game_building_units.game_building_id', $this->buildingId);
        })->select('game_units.*', 'game_building_units.required_level as required_building_level');
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $unitId = GameUnit::where('name', $value)->first()->id;

                if (! is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/kingdoms/units/'.$unitId.'">'.$row->name.'</a>';
                    }
                }

                return '<a href="/information/unit/'.$unitId.'" >'.$row->name.'</a>';
            })->html(),
            Column::make('Attack')->sortable(),
            Column::make('Defence')->sortable(),
            Column::make('Required Building Level', 'id')->format(function ($value, $row) {
                if (! is_null($this->buildingId)) {
                    return $row->required_building_level;
                }

                $unitId = GameUnit::find($value)->id;
                $gameBuildingUnit = GameBuildingUnit::where('game_unit_id', $unitId)->first();

                return $gameBuildingUnit?->required_level;
            }),
        ];
    }
}
