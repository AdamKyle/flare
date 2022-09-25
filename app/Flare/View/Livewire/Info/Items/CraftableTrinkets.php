<?php

namespace App\Flare\View\Livewire\Info\Items;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CraftableTrinkets extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('can_craft', true)
            ->where('type', 'trinket');
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                return '<a href="/items/'. $itemId.'" >'.$row->name . '</a>';
            })->html(),
            Column::make('Ambush Chance', 'ambush_chance')->sortable()->format(function ($value) {
                return $value * 100 . '%';
            }),
            Column::make('Ambush Resistance', 'ambush_resistance')->sortable()->format(function ($value) {
                return $value * 100 . '%';
            }),
            Column::make('Counter Chance', 'counter_chance')->sortable()->format(function ($value) {
                return $value * 100 . '%';
            }),
            Column::make('Counter Resistance', 'counter_resistance')->sortable()->format(function ($value) {
                return $value * 100 . '%';
            }),
            Column::make('Gold Dust Cost', 'gold_dust_cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Copper Coin Cost', 'copper_coin_cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Min Crafting Lv.', 'skill_level_required')->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable(),
        ];
    }
}
