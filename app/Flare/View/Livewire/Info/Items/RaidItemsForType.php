<?php

namespace App\Flare\View\Livewire\Info\Items;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class RaidItemsForType extends DataTableComponent {

    public string $type;

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Item::whereNull('item_prefix_id')
                   ->whereNull('item_suffix_id')
                   ->whereNotIn('type', ['quest', 'alchemy', 'trinket', 'artifact'])
                   ->where('specialty_type', $this->type);
    }

    public function filters(): array {
        return [
            SelectFilter::make('Types')
                ->options($this->buildOptions())
                ->filter(function(Builder $builder, string $value) {
                    return $builder->where('type', $value);
                }),
        ];
    }

    protected function buildOptions(): array {
        $options = [
            ''              => 'Please Select',
            'weapon'        => 'Weapons',
            'bow'           => 'Bows',
            'gun'           => 'Guns',
            'fan'           => 'Fans',
            'scratch-awl'   => 'Scratch Awls',
            'mace'          => 'Maces',
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

        return $options;
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

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
        ];
    }
}
