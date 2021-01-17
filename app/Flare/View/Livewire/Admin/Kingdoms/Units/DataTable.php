<?php

namespace App\Flare\View\Livewire\Admin\Kingdoms\Units;

use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithPagination;
    use WithSorting;
    
    protected $paginationTheme = 'bootstrap';
    
    public $search       = '';
    public $sortField    = 'name';
    public $perPage      = 10;
    public $editing      = false;
    public $building     = null;

    public function fetch() {

        if (!is_null($this->building)) {
            return GameBuildingUnit::where('game_building_id', $this->building->id)->join('game_units', function($join) {
                $query = $join->on('game_building_units.game_unit_id', '=', 'game_units.id');

                if ($this->search !== '') {
                    $query->where('name', 'like', '%'.$this->search.'%');
                }

                return $query;
            })->select('game_units.*', 'game_building_units.required_level')->orderBy($this->sortField, $this->sortBy)->paginate($this->perPage);
        }

        return GameUnit::dataTableSearch($this->search)->orderBy($this->sortField, $this->sortBy)->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.kingdoms.units.data-table', [
            'units' => $this->fetch(),
        ]);
    }
}
