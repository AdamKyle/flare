<?php

namespace App\Flare\View\Livewire\Admin\PassiveSkills;

use App\Flare\Models\PassiveSkill;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;
use Livewire\Component;
use Livewire\WithPagination;

class DataTable extends Component
{
    use WithPagination, WithSorting, WithPagination;

    public $search      = '';
    public $sortField   = 'name';
    public $perPage     = 10;
    public $only        = null;
    public $skillId     = 0;
    public $characterId = null;

    protected $paginationTheme = 'bootstrap';

    public function fetchPassiveSkills() {

        $skills = PassiveSkill::dataTableSearch($this->search);

        if ($this->skillId !== 0 && !is_null($this->only)) {
            if ($this->only === 'children') {
                $skills = $skills->where('parent_skill_id', $this->skillId);
            }
        }

        return $skills->orderBy($this->sortField, $this->sortBy)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.passive-skills.data-table', [
            'skills'      => $this->fetchPassiveSkills(),
            'characterId' => $this->characterId
        ]);
    }
}
