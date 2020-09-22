<?php

namespace App\Flare\View\Livewire\Core;

use Livewire\Component;

class DataTable extends Component
{
    public $search;

    public $sortField = 'id';

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
        return view('components.livewire.core.data-table');
    }
}
