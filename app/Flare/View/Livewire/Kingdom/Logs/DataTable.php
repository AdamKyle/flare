<?php

namespace App\Flare\View\Livewire\Kingdom\Logs;

use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSelectAll;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component {

    use WithSorting, WithPagination, WithSelectAll;

    public $attackLogs;

    public $character;

    public $search             = '';

    public $sortField          = 'items.status';

    public $perPage            = 10;

    protected $paginationTheme = 'bootstrap';

    public function getDataQueryProperty() {
        $this->character = $this->character;
        $logs            = $this->attackLogs;

        if (strval($this->search) !== '') {
            $logs = $logs->filter(function($log) {
                if (strpos($log->status, strval($this->search)) !== false) {
                    return $log;
                }
            })->all();
        }

        if (is_array($logs)) {
            $logs = collect($logs);
        }

        if ($this->sortBy === 'asc') {
            $logs = $logs->sortBy($this->sortField);
        }

        return $logs->transform(function($log) {
            if (is_null($log->from_kingdom)) {
                $log->from_kingdom_name = 'Unknown';
            } else {
                $log->from_kingdom_name = $log->from_kingdom->name . ' At (X/Y) ' . $log->from_kingdom->x_position . '/' . $log->from_kingdom->y_position;
            }

            $log->to_kingdom_name   = $log->to_kingdom->name . ' At (X/Y) ' . $log->to_kingdom->x_position . '/' . $log->to_kingdom->y_position;
            $log->time_happened     = $log->created_at->format('y-m-d g:i A');

            return $log;
        })->sortByDesc('created_at');
    }

    public function getDataProperty() {

        return $this->dataQuery->paginate($this->perPage);
    }

    public function fetchKingdomAttackLogs() {
        return $this->data;
    }

    public function render()
    {
        $this->selectAllRenderHook();

        return view('components.livewire.kingdom.logs.data-table', [
            'logs' => $this->fetchKingdomAttackLogs()
        ]);
    }
}
