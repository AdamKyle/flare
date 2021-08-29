<?php

namespace App\Flare\View\Livewire\Admin\Npcs;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Npc;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use App\Flare\Values\NpcTypes;

class DataTable extends Component {

    use WithPagination, WithSorting;

    public $search      = '';
    public $sortField   = 'name';
    public $perPage     = 10;
    public $forMap      = null;
    public $only        = null;

    public function fetchNpcs() {

        if (!is_null($this->forMap)) {
            $npcs = Npc::dataTableSearch($this->search)->where('game_map_id', $this->forMap)->get();
        } else {
            $npcs = Npc::dataTableSearch($this->search);

            if (!is_null($this->only)) {
                $npcs = $npcs->where('type', $this->only)->get();
            } else {
                $npcs = $npcs->get();
            }
        }

        $npcs = $npcs->transform(function($npc) {
            $npc->type        = (new NpcTypes($npc->type))->getNamedValue();

            return $npc;
        });

        if ($this->sortBy === 'desc') {
            return $npcs->sortBy($this->sortBy)->paginate(10);
        }

        return $npcs->sortByDesc($this->sortBy)->paginate(10);
    }

    public function render() {
        return view('components.livewire.admin.npcs.data-table', [
            'npcs' => $this->fetchNpcs(),
        ]);
    }
}
