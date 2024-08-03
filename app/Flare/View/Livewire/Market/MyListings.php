<?php

namespace App\Flare\View\Livewire\Market;

use App\Flare\Models\MarketBoard;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class MyListings extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return MarketBoard::where('character_id', auth()->user()->character->id);
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')->hideIf(true),
            Column::make('itemId', 'item_id')->hideIf(true),
            Column::make('Item name', 'item.name')->searchable()->format(
                fn ($value, $row, Column $column) => view('game.items.items-name-for-table')->withValue($row->item)
            ),
            Column::make('Listed For', 'listed_price')->format(
                fn ($value, $row, Column $column) => number_format($value)
            )->searchable()->sortable(),
            Column::make('Actions')->label(
                fn ($row, Column $column) => view('game.core.market.partials.market-my-actions-section', [
                    'row' => $row,
                ])
            ),
        ];
    }
}
