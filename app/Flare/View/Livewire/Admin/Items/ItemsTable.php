<?php

namespace App\Flare\View\Livewire\Admin\Items;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Flare\Models\Item;

class ItemsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Item::whereNotIn('type', ['quest', 'alchemy'])
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->when($this->getAppliedFilterWithValue('types'), fn ($query, $type) => $query->where('type', $type));
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options([
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

    public function columns(): array {
        return[
            Column::make('Name')->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                if (auth()->user()->hasRole('Admin')) {
                    return '<a href="/admin/items/'. $itemId.'">'.$row->name . '</a>';
                }

                return '<a href="/items/'. $itemId.'">'.$row->name . '</a>';
            })->html(),
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
}
