<?php

namespace App\Flare\View\Livewire\Admin\Quests;

use App\Flare\Models\Quest;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $search      = '';
    public $sortField   = 'name';
    public $perPage     = 10;
    public $forMap      = null;
    public $forNpc      = null;

    public function fetchQuests() {
        if (!is_null($this->forMap)) {
            $quests = Quest::join('npcs', function($join) {
                $join->on('npcs.id', 'quests.npc_id')
                     ->where('npcs.game_map_id', $this->forMap);
            });

            if ($this->search !== '') {
                $quests = $quests->where('quests.name', 'like', '%'.$this->search.'%');
            }

            $quests = $quests->select('quests.*')->get();
        } else if (!is_null($this->forNpc)) {
            $quests = Quest::dataTableSearch($this->search)->where('npc_id', $this->forNpc)->get();
        } else {
            $quests = Quest::dataTableSearch($this->search)->get();
        }

        $quests = $quests->transform(function($quest) {
            $quest->npc_name = $quest->npc->real_name;

            return $quest;
        });

        if ($this->sortBy === 'desc') {
            return $quests->sortBy($this->sortBy)->paginate(10);
        }

        return $quests->sortByDesc($this->sortBy)->paginate(10);
    }

    public function render()
    {
        return view('components.livewire.admin.quests.data-table', [
            'quests' => $this->fetchQuests()
        ]);
    }
}
