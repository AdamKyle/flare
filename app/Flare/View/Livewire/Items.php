<?php

namespace App\Flare\View\Livewire;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class Items extends DataTableComponent
{

    protected string $pageName = 'items';

    protected string $tableName = 'items';

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable(),
            Column::make('Type')->searchable()->format(function ($value) {
                return ucfirst(str_replace('-', ' ', $value));
            }),
            Column::make('Min Crafting Lv.', 'skill_level_required')->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable(),
            Column::make('Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
        ];
    }

    public function filters(): array
    {
        return [
            'type' => Filter::make('Item Type')
                ->select([
                    'weapon'        => 'Weapons',
                    'bow'           => 'Bows',
                    'body'          => 'Body',
                    'helmet'        => 'Helmets',
                    'shield'        => 'Shields',
                    'sleeves'       => 'Sleeves',
                    'gloves'        => 'Gloves',
                    'leggings'      => 'Leggings',
                    'feet'          => 'Feet',
                    'ring'          => 'Rings',
                    'artifact'      => 'Artifacts',
                    'spell-healing' => 'Healing Spells',
                    'spell-damage'  => 'Damage Spells',
                ]),
        ];
    }

    public function query(): Builder
    {

        return Item::whereNotIn('type', ['quest', 'alchemy'])
                   ->whereNull('item_prefix_id')
                   ->whereNull('item_suffix_id')
                   ->when($this->getFilter('type'), fn ($query, $type) => $query->where('type', $type));
    }
}
