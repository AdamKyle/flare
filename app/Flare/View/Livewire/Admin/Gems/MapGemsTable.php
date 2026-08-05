<?php

namespace App\Flare\View\Livewire\Admin\Gems;

use App\Flare\View\Tables\Definitions\MapGemsTableDefinition;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MapGemsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return MapGemsTableDefinition::builder();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->hideIf(true),
            Column::make('Name', 'name')
                ->searchable()
                ->format(fn ($value, $row) => '<a href="'.route('admin.map-gems.show', ['gameMapGemParamter' => $row->getRouteKey()]).'">'.e($row->name).'</a>')
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
            Column::make('Actions')
                ->label(fn ($row) => '<a href="'.route('admin.map-gems.edit', ['gameMapGemParamter' => $row->getRouteKey()]).'">Edit</a>')
                ->html(),
        ];
    }
}
