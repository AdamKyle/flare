<?php

namespace App\Flare\View\Livewire\Info;

use App\Flare\Models\GameMapGemParamters;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MapGems extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return GameMapGemParamters::query()->with('gameMap');
    }

    public function columns(): array
    {
        $columns = [
            Column::make('ID', 'id')->hideIf(true),
            Column::make('Name', 'name')
                ->searchable()
                ->format(function ($value, $row) {
                    $routeKey = $row->getRouteKey();
                    $route = route('info.page.map-gems.show', ['gameMapGemParamters' => $routeKey]);

                    if (auth()->check() && auth()->user()->hasRole('Admin')) {
                        $route = route('admin.map-gems.show', ['gameMapGemParamters' => $routeKey]);
                    }

                    return '<a href="'.$route.'">'.e($row->name).'</a>';
                })
                ->html(),
            Column::make('Map', 'gameMap.name')->searchable()->sortable(),
            Column::make('Monster Atonement', 'monster_atonement')
                ->format(fn ($value) => is_null($value) ? 'N/A' : GemTypeValue::getNames()[$value]),
            Column::make('Character XP Range', 'character_xp_bonus_range')
                ->format(fn ($value) => $value ?? 'N/A'),
            Column::make('Gold Gain Range', 'gold_gain_range')
                ->format(fn ($value) => $value ?? 'N/A'),
            Column::make('Crafting Skill Bonus Range', 'crafting_skill_bonus_range')
                ->format(fn ($value) => $value ?? 'N/A'),
        ];

        if (auth()->check() && auth()->user()->hasRole('Admin')) {
            $columns[] = Column::make('Actions')
                ->label(fn ($row) => '<a href="'.route('admin.map-gems.edit', ['gameMapGemParamters' => $row->getRouteKey()]).'">Edit</a>')
                ->html();
        }

        return $columns;
    }
}
