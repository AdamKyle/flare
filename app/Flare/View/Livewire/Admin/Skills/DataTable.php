<?php

namespace App\Flare\View\Livewire\Admin\Skills;


use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\GameSkill;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{

    use WithSorting, WithPagination;

    public $search  = '';

    public $sortField = 'name';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function fetchGameSkills() {
        $search = strtolower($this->search);

        if ($this->search !== '') {
            $this->page = 1;
        }

        if ($search === 'yes') {
            return GameSkill::where('can_train', true)
                            ->orderBy($this->sortField, $this->sortBy)
                            ->paginate($this->perPage);
        }

        if ($search === 'no') {
            return GameSkill::where('can_train', false)
                            ->orderBy($this->sortField, $this->sortBy)
                            ->paginate($this->perPage);
        }

        return GameSkill::dataTableSearch($this->search)
                        ->orderBy($this->sortField, $this->sortBy)
                        ->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.skills.data-table', [
            'gameSkills' => $this->fetchGameSkills(),
        ]);
    }
}
