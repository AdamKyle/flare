<?php

namespace App\Flare\View\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Flare\Models\User;
use App\Flare\View\Livewire\Core\DataTables\WithSorting;

class DataTable extends Component
{
    use WithPagination, WithSorting;

    public $search  = '';

    public $sortField = 'id';

    public $perPage = 10;

    protected $paginationTheme = 'bootstrap';

    public function fetchUsers() {
        $users = User::doesntHave('roles');

        if ($this->search !== '') {
            $this->page = 1;

            $users = $users->join('characters', function($join) {
                $join->on('users.id', 'characters.user_id')
                     ->where('characters.name', 'like', '%'.$this->search.'%');
            })->orderBy($this->sortField, $this->sortBy)->select('users.*');
        } else if ($this->sortField == 'characters.name') {
            $users->join('characters', function($join) {
                $join->on('users.id', 'characters.user_id');
            })->orderBy($this->sortField, $this->sortBy)->select('users.*');
        } else if ($this->sortField == 'characters.level') {
            $users->join('characters', function ($join) {
                $join->on('users.id', 'characters.user_id');
            })->orderBy($this->sortField, $this->sortBy)->select('users.*');
        } else {
            $users = $users->orderBy('un_ban_request', $this->sortBy)->orderBy($this->sortField, $this->sortBy);
        }

        return $users->paginate($this->perPage);
    }

    public function render()
    {
        return view('components.livewire.admin.users.data-table', [
            'users' => $this->fetchUsers(),
        ]);
    }
}
