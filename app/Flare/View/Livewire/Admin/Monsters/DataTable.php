<?php

namespace App\Flare\View\Livewire\Admin\Monsters;

use App\Flare\Models\CharacterSnapShot;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Monster;
use App\Flare\Models\User;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $adventureId = null;
    public $search      = '';
    public $sortField   = 'max_level';
    public $perPage     = 10;
    public $published   = true;
    public $canTest     = false;
    public $testCharacters = [];

    protected $paginationTheme = 'bootstrap';

    public function fetchMonsters() {
        $monsters = Monster::dataTableSearch($this->search);

        if (!is_null($this->adventureId)) {
            $monsters = $monsters->join('adventure_monster', function($join) {
                $join->on('adventure_monster.monster_id', 'monsters.id')
                     ->where('adventure_monster.adventure_id', $this->adventureId);
            })->select('monsters.*');
        }

        return $monsters->where('published', $this->published)
                        ->orderBy($this->sortField, $this->sortBy)
                        ->paginate($this->perPage);
    }

    public function mount() {
        $this->canTest = User::where('is_test', true)->get()->isNotEmpty();

        $this->testCharacters = User::with('character')->where('is_test', true)->get();
    }
    
    public function render()
    {
        return view('components.livewire.admin.monsters.data-table', [
            'monsters' => $this->fetchMonsters()
        ]);
    }
}
