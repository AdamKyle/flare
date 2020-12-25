<?php

namespace App\Flare\View\Livewire\Admin\Adventures;

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

    public $canTest        = false;
    public $testCharacters = null;

    protected $paginationTheme = 'bootstrap';

    public function mount() {
        $this->canTest        = User::where('is_test', true)->get()->isNotEmpty() && !Cache::has('processing-adventure');
        $this->testCharacters = User::with('character')->where('is_test', true)->get();
    }

    public function render()
    {
        return view('components.livewire.admin.adventures.data-table', [
            'adventures' => Adventure::dataTableSearch($this->search)
                                 ->where('published', $this->published)
                                 ->orderBy($this->sortField, $this->sortBy)
                                 ->paginate($this->perPage),
        ]);
    }
}
