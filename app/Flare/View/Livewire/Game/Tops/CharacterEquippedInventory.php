<?php

namespace App\Flare\View\Livewire\Game\Tops;

use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\SetSlot;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CharacterEquippedInventory extends DataTableComponent
{
    public int $characterId;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {

        $inventorySetEquipped = InventorySet::where('is_equipped', true)->where('character_id', $this->characterId)->first();

        if (! is_null($inventorySetEquipped)) {
            return SetSlot::where('inventory_set_id', $inventorySetEquipped->id);
        }

        $inventory = Inventory::where('character_id', $this->characterId)->first();

        return InventorySlot::where('equipped', true)->where('inventory_id', $inventory->id);
    }

    public function columns(): array
    {
        return [
            Column::make('id', 'id')->hideIf(true),
            Column::make('itemId', 'item_id')->hideIf(true),
            Column::make('Item name', 'item.name')->searchable()->format(
                fn ($value, $row, Column $column) => view('game.items.items-name-for-table')->withValue($row->item)
            ),
            Column::make('Position', 'position')->searchable()->format(
                fn ($value, $row, Column $column) => ucfirst(str_replace('-', ' ', $value))
            ),
        ];
    }
}
