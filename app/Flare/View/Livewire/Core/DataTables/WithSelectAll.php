<?php

namespace App\Flare\View\Livewire\Core\DataTables;

trait WithSelectAll {

    public $pageSelected = [];
    public $selected     = [];
    public $selectAll    = false;

    public function updatedPageSelected($value) {
        if (empty($value)) {
            $this->selectAll    = false;
            $this->pageSelected = false;
            $this->selected     = [];
        } else {
            $this->selected = $this->data->pluck('id')->map(fn($id) => (string) $id);
        }
    }

    public function selectAll() {
        $this->selectAll = true;
    }

    public function updatedSelected() {
        $this->selectAll    = false;
        $this->pageSelected = false;
    }

    public function selectAllRenderHook() {
        if ($this->selectAll) {
            $this->selected = $this->dataQuery->pluck('id')->map(fn($id) => (string) $id);

            dump($this->selected);
        }
    }
}