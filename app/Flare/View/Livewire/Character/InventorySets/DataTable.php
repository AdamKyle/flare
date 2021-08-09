<?php

namespace App\Flare\View\Livewire\Character\InventorySets;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{

    public $search             = '';

    public $perPage            = 10;

    public $sortField          = 'items.type';

    protected $paginationTheme = 'bootstrap';

    public $inventorySet;

    public $character;

    use WithPagination, WithSorting;

    public function getDataProperty() {
        $slots = $this->inventorySet->slots()->select('set_slots.*')->join('items', function($join) {
            $join->on('set_slots.item_id', '=', 'items.id');
        })->orderBy($this->sortField, $this->sortBy)->get();

        if ($this->search !== '') {
            $slots = collect($slots->filter(function ($slot) {
                return str_contains($slot->item->affix_name, $this->search) || str_contains($slot->item->name, $this->search);
            })->all());
        }

        return $slots->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.character.inventory-sets.data-table', [
            'slots' => $this->data,
        ]);
    }
}
