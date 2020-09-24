<?php

namespace App\Flare\View\Livewire\Admin\Items;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Item;

class DataTable extends CoreDataTable
{
    public function mount() {
        $this->sortField = 'type';

        $this->sortAsc = false;
    }

    public function fetchItems() {
        if (auth()->user()->hasRole('Admin')) {
            return Item::dataTableSearch($this->search)
                       ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                       ->paginate($this->perPage);
        }

        return Item::dataTableSearch($this->search)
                       ->where('type', '!=', 'quest')
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
