<?php

namespace App\Flare\View\Livewire\Character\Inventory;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Item;

class DataTable extends CoreDataTable
{
    public function mount() {
        $this->sortField = 'items.type';

        $this->sortAsc = false;
    }

    public function fetchSlots() {
        $character = auth()->user()->character;

        $slots = $character->inventory->slots()->join('items', function($join) {
            $join = $join->on('inventory_slots.item_id', '=', 'items.id')->where('type', '!=', 'quest');

            if ($this->search !== '') {
                $join->where('items.name', 'like', '%'.$this->search.'%');
            }

            return $join;
        })->where('equipped', false)
          ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
          ->select('inventory_slots.*')
          ->get();
        
        return $slots->paginate($this->perPage);
    }

    public function render()
    {
        
        return view('components.livewire.character.inventory.data-table', [
            'slots' => $this->fetchSlots(),
        ]);
    }
}
