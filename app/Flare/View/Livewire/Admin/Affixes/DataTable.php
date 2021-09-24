<?php

namespace App\Flare\View\Livewire\Admin\Affixes;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\ItemAffix;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $search      = '';
    public $sortField   = 'skill_level_required';
    public $perPage     = 10;
    public $only        = null;

    protected $paginationTheme = 'bootstrap';

    public function fetchAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchClassBonusAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('class_bonus', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchDamageBonusAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('damage', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchLifeStealingAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->whereNotNull('steal_life_amount')
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchStatReductionAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('reduces_enemy_stats', true)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function fetchEntrancingAffixes() {
        return ItemAffix::dataTableSearch($this->search)
            ->where('entranced_chance', '>', 0)
            ->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function render()
    {
        if ($this->search !== '') {
            $this->page = 1;
        }

        $data = $this->fetchAffixes();

        if (!is_null($this->only)) {
            switch ($this->only) {
                case 'class_bonus':
                    $data =  $this->fetchClassBonusAffixes();
                    break;
                case 'damage':
                    $data = $this->fetchDamageBonusAffixes();
                    break;
                case 'life_stealing':
                    $data = $this->fetchLifeStealingAffixes();
                    break;
                case 'stat_reduction':
                    $data = $this->fetchStatReductionAffixes();
                    break;
                case 'entrancing_chance':
                    $data = $this->fetchEntrancingAffixes();
                    break;
                case 'default':
                    $data = $this->fetchAffixes();
                    break;
            }
        }

        return view('components.livewire.admin.affixes.data-table', [
            'itemAffixes' => $data,
        ]);
    }
}
