<?php

namespace App\Flare\View\Livewire\Character\CompletedQuests;

use App\Flare\Models\QuestsCompleted;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithSorting, WithPagination;

    public $completedQuests;

    public $character;

    public $search             = '';

    public $sortField          = 'completedQuests.name';

    public $perPage            = 10;

    protected $paginationTheme = 'bootstrap';

    public function getDataQueryProperty() {
        return QuestsCompleted::where('character_id', $this->character->id)->get();
    }

    public function getDataProperty() {

        $data = $this->dataQuery;

        $data = $data->transform(function($item) {
           $item->name       = $item->quest->name;
           $item->created_at = $item->created_at->format('y-m-d g:i A');

           return $item;
        });

        if ($this->search !== '') {
            $data = $data->filter(function($item) {
                return str_contains($item->name, $this->search);
            })->all();

            $data = collect($data);
        }

        if ($this->sortBy === 'asc') {
            $data = $data->sortBy($this->sortField);
        } else {
            $data = $data->sortByDesc($this->sortField);
        }

        return $data->paginate($this->perPage);
    }

    public function fetchCompletedQuests() {
        return $this->data;
    }

    public function render()
    {
        return view('components.livewire.character.completed-quests.data-table', [
            'quests'    => $this->fetchCompletedQuests(),
            'character' => $this->character,
        ]);
    }
}
