<?php

namespace App\Flare\View\Livewire\Admin\Quests;

use App\Flare\Models\Npc;
use App\Flare\Values\NpcTypes;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class Datatable extends Component
{
    use WithPagination, WithSorting;

    public $search      = '';
    public $sortField   = 'name';
    public $perPage     = 10;

    public function fetchNpcs() {
        $quests = Quests::dataTableSearch($this->search)->get();

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
        return view('components.livewire.admin.quests.datatable');
    }
}
