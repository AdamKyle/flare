<?php

namespace App\Flare\View\Livewire\Core\DataTables;

trait WithSorting {

    public $sortBy = 'asc';

    public function sortBy($field) {
        $this->page = 1;
        
        if ($this->sortField === $field) {
            if ($this->sortBy === 'asc') {
                $this->sortBy = 'desc';
            } else {
                $this->sortBy = 'asc';
            }
        } else {
            $this->sortBy = 'asc';
        }

        $this->sortField = $field;
    }
}
