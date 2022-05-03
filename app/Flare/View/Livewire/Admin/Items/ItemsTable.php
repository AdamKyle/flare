<?php

namespace App\Flare\View\Livewire\Admin\Items;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Flare\Models\Item;

class ItemsTable extends DataTableComponent {

    public $isShop = false;

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        if (auth()->user()->hasRole('Admin')) {
            $item = Item::query();
        } else {
            $item = Item::whereNotIn('type', ['quest', 'alchemy']);
        }

        if ($this->isShop) {
            $item = $item->whereNotIn('type', ['trinket', 'quest', 'alchemy'])->where('cost', '<=', 2000000000);
        }

        return $item->whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->when($this->getAppliedFilterWithValue('types'), fn ($query, $type) => $query->where('type', $type));
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions()),
        ];
    }

    protected function buildOptions(): array {
        $options = [
            'weapon'        => 'Weapons',
            'bow'           => 'Bows',
            'stave'         => 'Staves',
            'hammer'        => 'Hammers',
            'body'          => 'Body',
            'helmet'        => 'Helmets',
            'shield'        => 'Shields',
            'sleeves'       => 'Sleeves',
            'gloves'        => 'Gloves',
            'leggings'      => 'Leggings',
            'feet'          => 'Feet',
            'ring'          => 'Rings',
            'spell-healing' => 'Healing Spells',
            'spell-damage'  => 'Damage Spells',
        ];

        if (auth()->user()->hasRole('Admin')) {
            $options['trinket'] = 'Trinkets';
            $options['quest']   = 'Quest items';
        }

        return $options;
    }

    public function columns(): array {
        $columns = [
            Column::make('Name')->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                if (auth()->user()->hasRole('Admin')) {
                    return '<a href="/admin/items/'. $itemId.'">'.$row->name . '</a>';
                }

                return '<a href="/items/'. $itemId.'" >'.$row->name . '</a>';
            })->html(),
            Column::make('Type')->searchable()->format(function ($value) {
                return ucfirst(str_replace('-', ' ', $value));
            }),

            Column::make('Damage', 'base_damage')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('AC', 'base_ac')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Healing', 'base_healing')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
        ];

        if (!auth()->user()->hasRole('Admin')) {
            $columns[] = Column::make('Actions')->label(
                fn($row, Column $column)  => view('admin.items.table-components.shop-actions-section', [
                    'character' => auth()->user()->character
                ])->withRow($row)
            );
        } else {
            $columns[] = Column::make('Min Crafting Lv.', 'skill_level_required')->sortable();
            $columns[] = Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable();
        }

        return $columns;
    }
}
