<?php

namespace App\Flare\View\Livewire\Character\Adventures;

use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithSorting, WithPagination;
    
    public $adventureLogs;

    public $search             = '';

    public $sortField          = 'items.type';

    public $perPage            = 10;

    protected $paginationTheme = 'bootstrap';

    public function fetchAdventureLogs() {
        $logs = $this->adventureLogs;

        if (strval($this->search) !== '') {
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

        return $logs->paginate($this->perPage);
    }
    
    public function render()
    {
        return view('components.livewire.character.adventures.data-table', [
            'logs' => $this->fetchAdventureLogs(),
        ]);
    }
}
