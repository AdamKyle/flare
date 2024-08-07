<?php

namespace App\Flare\View\Livewire\Character\Inventory;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CharacterInventory extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return auth()->user()->character->inventory->slots()->join('items', function ($join) {
            $join->on('inventory_slots.item_id', '=', 'items.id')
                ->whereNotIn('items.type', ['quest', 'alchemy', 'trinket'])
                ->where('items.usable', false);
        })->where('equipped', false)->select('inventory_slots.*')->getQuery();
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->hideIf(true),
            Column::make('Name', 'item.name')->format(function ($value, $row) {
                $item = Item::where('name', $value)->first();

                return '<a href="/items/'.$item->id.'">'.$item->affix_name.'</a>';
            })->html(),
            Column::make('Type', 'item.type')->searchable()->format(function ($value) {
                return ucfirst(str_replace('-', ' ', $value));
            })->sortable(),
            Column::make('Damage', 'item.base_damage')->sortable()->format(function ($value) {
                return number_format($value);
            })->sortable(),
            Column::make('AC', 'item.base_ac')->sortable()->format(function ($value) {
                return number_format($value);
            })->sortable(),
            Column::make('Healing', 'item.base_healing')->sortable()->format(function ($value) {
                return number_format($value);
            })->sortable(),
            Column::make('Cost', 'item.cost')->sortable()->format(function ($value) {
                return number_format($value);
            })->sortable(),
            Column::make('Actions')->label(
                fn ($row, Column $column) => view('game.shop.actions.sell-actions', [
                    'character' => auth()->user()->character,
                ])->withRow($row)
            ),
        ];
    }
}
