<?php

namespace App\Flare\View\Livewire\Info\AlchemyItems;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AlchemyHolyItemsTable extends DataTableComponent
{
    /**
     * Configures the table by setting its primary key.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    /**
     * Builds the query to retrieve holy alchemy items.
     *
     * @return Builder
     */
    public function builder(): Builder
    {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', 'alchemy')
            ->where('can_use_on_other_items', true)
            ->orderBy('skill_level_required', 'asc');
    }

    /**
     * Defines the columns displayed in the table.
     *
     * @return array
     */
    public function columns(): array
    {
        return [
            Column::make('Name')
                ->searchable()
                ->format(function ($value, $row) {
                    $itemId = Item::where('name', $value)->first()->id;
                    return '<a href="/items/' . $itemId . '">' . $row->name . '</a>';
                })
                ->html(),
            Column::make('Gold Dust Cost')
                ->sortable()
                ->format(function ($value) {
                    return number_format($value);
                }),
            Column::make('Shards Cost')
                ->sortable()
                ->format(function ($value) {
                    return number_format($value);
                }),
            Column::make('Min Crafting Lv.', 'skill_level_required')
                ->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')
                ->sortable(),
        ];
    }
}
