<?php

namespace App\Flare\View\Livewire\Admin\Adventures;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Adventure;

class DataTable extends CoreDataTable
{
    public function mount() {
        $this->sortField = 'levels';
    }

    public function render()
    {
        return view('components.livewire.admin.adventures.data-table', [
            'adventures' => Adventure::dataTableSearch($this->search)
                                 ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                 ->paginate($this->perPage),
        ]);
    }
}
