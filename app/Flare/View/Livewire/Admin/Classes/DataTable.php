<?php

namespace App\Flare\View\Livewire\Admin\Classes;

use App\Flare\Models\GameClass;
use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as BaseDataTable;

class DataTable extends BaseDataTable
{
    public function render()
    {
        return view('components.livewire.admin.classes.data-table', [
            'gameClasses' => GameClass::dataTableSearch($this->search)
                                       ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                       ->paginate($this->perPage),
        ]);
    }
}
