<?php

namespace App\Flare\View\Livewire\Admin\Users;

use App\Flare\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UserTable extends DataTableComponent
{

    public function columns(): array
    {
        return [
            Column::make('User ID', 'id'),
            Column::make('Character Name', 'character.name'),
            Column::make('Character Level', 'character.level'),
            Column::make('Last Logged In At', 'last_logged_in'),
        ];
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }


    public function builder(): Builder
    {
        return User::where('email', '!=', 'adamkylebalan@gmail.com');
    }
}
