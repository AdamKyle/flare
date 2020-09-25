<?php

namespace App\Flare\View\Livewire\Character\Adventures;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;

class DataTable extends CoreDataTable
{
    public $adventureLogs;

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
  
        if ($this->sortAsc) {
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
