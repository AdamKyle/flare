<?php

namespace App\Flare\View\Livewire\Info\QuestItems;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class QuestItemsTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Item::whereNull('item_prefix_id')
                   ->whereNull('item_suffix_id')
                   ->where('type', 'quest');
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
            Column::make('Description', 'description'),
        ];
    }
}
