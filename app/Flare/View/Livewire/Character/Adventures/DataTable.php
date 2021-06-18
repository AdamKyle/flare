<?php

namespace App\Flare\View\Livewire\Character\Adventures;

use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithSorting, WithPagination, WithSelectAll;

    public $adventureLogs;

    public $character;

    public $search             = '';

    public $sortField          = 'items.name';

    public $perPage            = 10;

    protected $paginationTheme = 'bootstrap';


    public function getDataQueryProperty() {
        $logs      = $this->adventureLogs;
        $character = $this->character;

        if (strval($this->search) !== '') {
            $this->page = 1;

            $logs = $logs->filter(function($log) {
                if (strpos($log->adventure->name, strval($this->search)) !== false) {
                    return $log;
                }
            })->all();
        }

        if (is_array($logs)) {
            $logs = collect($logs);
        }

        if ($this->sortBy === 'asc') {
            $logs = $logs->sortBy($this->sortField);
        } else {
            $logs = $logs->sortByDesc($this->sortField);
        }

        return $logs;
    }

    public function getDataProperty() {

        return $this->dataQuery->paginate($this->perPage);
    }

    public function fetchAdventureLogs() {
        return $this->data;
    }

    public function render()
    {
        return view('components.livewire.character.adventures.data-table', [
            'logs'      => $this->fetchAdventureLogs(),
            'character' => $this->character
        ]);
    }
}
