<?php

namespace App\Flare\View\Livewire\Character\Inventory;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Item;

class DataTable extends CoreDataTable
{
    public $includeEquipped   = false;

    public $includeQuestItems = false;

    public $allowUnequipAll   = false;

    public $allowInventoryManagement = false;

    public $batchSell = false;

    public $character;

    public function mount() {
        $this->sortField = 'items.type';

        $this->sortAsc = false;
    }

    public function fetchSlots() {
        $character = auth()->user()->character;

        if (!is_null($this->character)) {
            $character = $this->character;
        }

        $slots = $character->inventory->slots()->join('items', function($join) {
            $join = $join->on('inventory_slots.item_id', '=', 'items.id');

            if ($this->search !== '') {
                $join->where('items.name', 'like', '%'.$this->search.'%');
            }

            if (!$this->includeQuestItems) {
                $join->where('items.type', '!=', 'quest');
            }

            return $join;
        });
        
        $slots->where('equipped', $this->includeEquipped)
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
