<?php

namespace App\Flare\View\Livewire\Admin\Users;

use App\Flare\Models\User;
use Livewire\Component;
use Facades\App\Flare\Values\UserOnlineValue;
use App\Flare\View\Livewire\Core\DataTable as CoreDataTable;
use Illuminate\Database\Eloquent\Collection;

class DataTable extends CoreDataTable
{

    public function mount() {
        $this->sortField = 'email';
    }

    public function fetchUsers() {
        $users = User::all();
        
        if ($this->sortField === 'characters.name') {
            $users = User::join('characters', function($join) {
                $join = $join->on('users.id', '=', 'characters.user_id');

                if ($this->search !== '') {
                    $join->where('characters.name', 'like', '%'.$this->search.'%');
                }

                return $join;
                     
            })
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->select('users.*')
            ->get();


            return $this->transformUsers($users)->paginate($this->perPage);
        }

        if ($this->sortField === 'user.currently_online') {
            $users = $this->transformUsers(User::all());

            if ($this->sortAsc) {
                return $users->sortBy('currently_online')->paginate($this->perPage);
            }
            
    
            return $users->sortByDesc('currently_online')->paginate($this->perPage);
        }

        if ($this->search !== '') {
            $users = User::join('characters', function($join) {
                return $join->on('users.id', '=', 'characters.user_id')
                            ->where('characters.name', 'like', '%'.$this->search.'%');
                     
            })
            ->orderBy($this->sortField, $this->sortAsc ? 'asc' : 'desc')
            ->select('users.*')
            ->get();

            if ($users->isEmpty()) {
                $users = User::dataTableSearch($this->search)->get();

                if ($users->isEmpty()) {

                    $users = $this->transformUsers(User::all())->filter(function($user) {
                        if (($user->currently_online ? 'yes' : 'no') === strtolower($this->search)) {
                            return $user;
                        }
                    })->all();

                    return collect($users)->paginate($this->perPage);
                }
            }
            
            return $this->transformUsers($users)->paginate($this->perPage);
        }

        $users = $this->transformUsers($users);

        if ($this->sortAsc) {
            return $users->sortBy($this->sortField)->paginate($this->perPage);
        }
        

        return $users->sortByDesc($this->sortField)->paginate($this->perPage);
    }

    protected function transformUsers(Collection $users): Collection {
        $users->transform(function($user) {
            $user->currently_online = UserOnlineValue::isOnline($user);

            return $user;
        });

        return $users;
    }

    public function render()
    {
        return view('components.livewire.admin.users.data-table',[
            'users' => $this->fetchUsers(),
        ]);
    }
}
