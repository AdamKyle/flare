<?php

namespace App\Flare\View\Livewire\Admin\Adventures;

use App\Flare\Models\Character;
use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\Adventure;
use App\Flare\Models\User;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Cache;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $adventureId    = null;
    public $search         = '';
    public $sortField      = 'name';
    public $perPage        = 10;
    public $published      = true;
    public $gameMapId      = null;

    public $canTest        = false;
    public $testCharacters = null;

    protected $paginationTheme = 'bootstrap';

    public function mount() {
        $this->canTest        = Character::whereNull('user_id')->get()->isNotEmpty() && !Cache::has('processing-adventure');
        $this->testCharacters = Character::whereNull('user_id')->get();
    }

    public function render()
    {
        if ($this->search !== '') {
            $this->page = 1;
        }

        $adventures = Adventure::where('published', $this->published);

        if (!is_null($this->gameMapId)) {
            $adventures = $adventures->join('adventure_location', function($join) {
                $join->on('adventure_location.adventure_id', 'adventures.id')
                      ->join('locations', 'locations.id', '=', 'adventure_location.location_id')
                      ->join('game_maps', 'game_maps.id', '=', 'locations.game_map_id')
                      ->where('game_maps.id', $this->gameMapId);
            })->select('adventures.*');
        }

        $adventures = $adventures->where('adventures.name', 'like', '%'.$this->search.'%');

        $adventures = $adventures->orderBy($this->sortField, $this->sortBy)
                                 ->paginate($this->perPage);

        return view('components.livewire.admin.adventures.data-table', [
            'adventures' => $adventures,
        ]);
    }
}
