<?php

namespace App\Flare\View\Livewire\Character\Inventory;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;

class DataTable extends Component
{
    use WithPagination, WithSorting, WithSelectAll;

    public $search                   = '';

    public $sortField                = 'items.type';

    public $perPage                  = 10;

    protected $paginationTheme       = 'bootstrap';

    public $includeEquipped          = false;

    public $includeQuestItems        = false;

    public $allowUnequipAll          = false;

    public $allowInventoryManagement = false;

    public $batchSell                = false;

    public $character;

    public function getDataProperty() {
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
              ->orderBy($this->sortField, $this->sortBy)
              ->select('inventory_slots.*')
              ->get();
        
        return $slots->paginate($this->perPage);
    }

    public function fetchSlots() {
        return $this->data;
    }

    public function render()
    {
        
        return view('components.livewire.character.inventory.data-table', [
            'slots' => $this->fetchSlots(),
        ]);
    }
}
