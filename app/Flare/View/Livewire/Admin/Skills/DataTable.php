<?php

namespace App\Flare\View\Livewire\Admin\Skills;

use App\Flare\Models\GameSkill;
use App\Flare\View\Livewire\Core\DataTable as BaseDataTable;

class DataTable extends BaseDataTable
{

    public function fetchGameSkills() {
        $search = strtolower($this->search);

        if ($search === 'yes') {
            return GameSkill::where('can_train', true)
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate($this->perPage);
        }

        if ($search === 'no') {
            return GameSkill::where('can_train', false)
                            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                            ->paginate($this->perPage);
        }

        return GameSkill::dataTableSearch($this->search)
                        ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
                        ->paginate($this->perPage);
    }
    
    public function render()
    {
        return view('components.livewire.admin.skills.data-table', [
            'gameSkills' => $this->fetchGameSkills(),
        ]);
    }
}
