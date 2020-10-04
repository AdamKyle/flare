<?php

namespace App\Flare\View\Livewire\Admin\Races;

use App\Flare\Models\GameRace;
use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as BaseDataTable;

class DataTable extends BaseDataTable
{
    public function render()
    {
        return view('components.livewire.admin.races.data-table', [
            'races' => GameRace::dataTableSearch($this->search)
                               ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                               ->paginate($this->perPage),
        ]);
    }
}
