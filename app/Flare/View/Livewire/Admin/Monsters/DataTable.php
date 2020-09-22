<?php

namespace App\Flare\View\Livewire\Admin\Monsters;

use Livewire\Component;
use App\Flare\Models\Monster;

class DataTable extends Component
{

    public $search;

    public $sortField = 'max_level';

    public $sortAsc = true;

    public $perPage = 10;

    public function sortBy(string $fieldName) {
        if ($this->sortField === $fieldName) {
            $this->sortAsc = !$this->sortAsc;
        } else {
            $this->sortAsc = true;
        }

        $this->sortField = $fieldName;
    }

    public function render()
    {
        return view('components.livewire.admin.monsters.data-table', [
            'monsters' => Monster::dataTableSearch($this->search)
                                 ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                                 ->paginate($this->perPage),
        ]);
    }
}
