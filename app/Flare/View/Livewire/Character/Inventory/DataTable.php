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

    public $marketBoard              = false;

    public $character;

    public function getDataQueryProperty() {
        if (!is_null($this->character)) {
            $character = $this->character;
        }

        $slots = $character->inventory->slots()->join('items', function($join) {
            $join = $join->on('inventory_slots.item_id', '=', 'items.id');

            if (!$this->includeQuestItems) {
                $join->where('items.type', '!=', 'quest');
            }

            if ($this->batchSell) {
                $join->whereNull('items.item_prefix_id')->whereNull('items.item_suffix_id');
            }

            if ($this->marketBoard) {
                $join->where('items.market_sellable', true);
            }

            return $join;
        });

        return $slots->select('inventory_slots.*')
              ->where('equipped', $this->includeEquipped)
              ->orderBy($this->sortField, $this->sortBy);
    }

    public function getDataProperty() {
        $slots = $this->dataQuery->get();

        if ($this->search !== '') {
            return collect($slots->filter(function ($slot) {
                return str_contains($slot->item->affix_name, $this->search) || str_contains($slot->item->name, $this->search);
            })->all())->paginate($this->perPage);
        }

        return $slots->paginate($this->perPage);
    }

    public function fetchSlots() {
        return $this->data;
    }

    public function render()
    {
        $this->selectAllRenderHook();

        return view('components.livewire.character.inventory.data-table', [
            'slots' => $this->fetchSlots(),
        ]);
    }
}
