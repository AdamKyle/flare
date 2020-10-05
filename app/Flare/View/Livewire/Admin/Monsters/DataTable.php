<?php

namespace App\Flare\View\Livewire\Admin\Monsters;

use Livewire\Component;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use App\Flare\Models\Monster;

class DataTable extends CoreDataTable
{

    public $adventureId = null;

    public function mount() {
        $this->sortField = 'max_level';
    }

    public function fetchMonsters() {
        $monsters = Monster::dataTableSearch($this->search);

        if (!is_null($this->adventureId)) {
            $monsters = $monsters->join('adventure_monster', function($join) {
                $join->on('adventure_monster.monster_id', 'monsters.id')
                     ->where('adventure_monster.adventure_id', $this->adventureId);
            })->select('monsters.*');
        }

        return $monsters->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                        ->paginate($this->perPage);
    }
    
    public function render()
    {
        return view('components.livewire.admin.monsters.data-table', [
            'monsters' => $this->fetchMonsters()
        ]);
    }
}
