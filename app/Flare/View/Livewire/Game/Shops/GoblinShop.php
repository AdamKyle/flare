<?php

namespace App\Flare\View\Livewire\Game\Shops;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Columns\LinkColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use App\Flare\Models\Item;

class GoblinShop extends DataTableComponent {

    public $isShop = false;

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {

        return Item::whereNull('item_prefix_id')
                    ->whereNull('item_suffix_id')
                    ->where('gold_bars_cost', '>', 0)
                    ->orderBy('gold_bars_cost', 'asc');
    }

    public function columns(): array {
        $columns = [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $itemId = Item::where('name', $value)->first()->id;

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/items/'. $itemId.'">'.$row->name . '</a>';
                    }
                }

                return '<a href="/items/'. $itemId.'" >'.$row->name . '</a>';
            })->html(),
            Column::make('Type')->searchable()->format(function ($value) {
                return ucfirst(str_replace('-', ' ', $value));
            }),
            Column::make('Lasts for', 'lasts_for')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Gold Bars (Cost)', 'gold_bars_cost')->sortable()->format(function ($value) {
                return number_format($value);
            }),
        ];

        if (!is_null(auth()->user())) {
            if (!auth()->user()->hasRole('Admin') && $this->isShop) {
                $columns[] = Column::make('Actions')->label(
                    fn($row, Column $column) => view('admin.items.table-components.goblin-shop-actions-section', [
                        'character' => auth()->user()->character
                    ])->withRow($row)
                );
            }
        }

        return $columns;
    }
}
