<?php

namespace App\Flare\View\Livewire\Core\DataTables;

trait WithSorting {

    public $sortField = 'id';

    public $sortBy = 'asc';

    public function sortBy($field) {
        if ($this->sortField === $field) {
            $this->sortBy = $this->sortBy === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = 'asc';
        }

        $this->sortField = $field;
    }
}