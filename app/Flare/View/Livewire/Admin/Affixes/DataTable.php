<?php

namespace App\Flare\View\Livewire\Admin\Affixes;

use App\Flare\Models\ItemAffix;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;

class DataTable extends CoreDataTable
{
    public function mount() {
        $this->sortField = 'name';
    }

    public function render()
    {
        return view('components.livewire.admin.affixes.data-table', [
            'itemAffixes' => ItemAffix::dataTableSearch($this->search)
                                      ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                      ->paginate($this->perPage),
        ]);
    }
}
