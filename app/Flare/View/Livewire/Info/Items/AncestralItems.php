<?php

namespace App\Flare\View\Livewire\Info\Items;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AncestralItems extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Item::whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->where('type', 'artifact')
            ->whereDoesntHave('itemSkillProgressions');
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                return '<a href="/items/'.$itemId.'" >'.$row->name.'</a>';
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
