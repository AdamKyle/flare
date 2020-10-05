<?php

namespace App\Flare\View\Livewire\Admin\Items;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Item;

class DataTable extends CoreDataTable
{
    public $affixId = null;
    
    public function mount() {
        $this->sortField = 'type';

        $this->sortAsc = false;
    }

    public function fetchItems() {
        if (auth()->user()->hasRole('Admin')) {
            $items = Item::dataTableSearch($this->search);

            if (!is_null($this->affixId)) {
                $items = $items->where('item_suffix_id', $this->affixId)
                               ->orWhere('item_prefix_id', $this->affixId);
            }
            
            return $items->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                         ->paginate($this->perPage);
        }

        return Item::dataTableSearch($this->search)
                       ->where('type', '!=', 'quest')
                       ->where('item_suffix_id', null)
                       ->where('item_prefix_id', null)
                       ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                       ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.items.data-table', [
            'items' => $this->fetchItems(),
        ]);
    }
}
