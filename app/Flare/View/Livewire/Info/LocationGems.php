<?php

namespace App\Flare\View\Livewire\Info;

use App\Flare\Models\GameLocationGemParamter;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class LocationGems extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return GameLocationGemParamter::query()->with('location.map');
    }

    public function columns(): array
    {
        $columns = [
            Column::make('ID', 'id')->hideIf(true),
            Column::make('Name', 'name')
                ->searchable()
                ->format(function ($value, $row) {
                    $routeKey = $row->getRouteKey();
                    $route = route('info.page.location-gems.show', ['gameLocationGemParamter' => $routeKey]);

                    if (auth()->check() && auth()->user()->hasRole('Admin')) {
                        $route = route('admin.location-gems.show', ['gameLocationGemParamter' => $routeKey]);
                    }

                    return '<a href="'.$route.'">'.e($row->name).'</a>';
                })
                ->html(),
            Column::make('Location', 'location.name')->searchable()->sortable(),
            Column::make('Map', 'location.map.name')->searchable()->sortable(),
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
                ->label(fn ($row) => '<a href="'.route('admin.location-gems.edit', ['gameLocationGemParamter' => $row->getRouteKey()]).'">Edit</a>')
                ->html();
        }

        return $columns;
    }
}
