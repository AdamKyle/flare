<?php

namespace App\Flare\View\Livewire\Info\AlchemyItems;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AlchemyKingdomItemsTable extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', 'alchemy')
            ->where('damages_kingdoms', true)
            ->where('can_use_on_other_items', false)
            ->orderBy('skill_level_required', 'asc');
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                return '<a href="/items/'.$itemId.'" >'.$row->name.'</a>';
            })->html(),
            Column::make('Gold Dust Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Shards Cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Min Crafting Lv.', 'skill_level_required')->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable(),
        ];
    }
}
