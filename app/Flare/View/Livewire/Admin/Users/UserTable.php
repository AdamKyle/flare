<?php

namespace App\Flare\View\Livewire\Admin\Users;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class UserTable extends DataTableComponent
{

    public function columns(): array
    {
        return [
            Column::make('User ID', 'id')->sortable(),
            Column::make('Character Name', 'character.name')->searchable()->format(function ($value, $row) {
                $character = Character::where('name', $value)->first();

                if (!is_null($character->user->un_ban_request)) {
                    return '<a href="/admin/user/'. $character->user->id.'" class="text-red-600 dark:text-red-500">'.$value.' <i class="fas fa-question"></i></a>';
                }

                if ($character->user->is_banned) {
                    return '<a href="/admin/user/'. $character->user->id.'" class="text-red-600 dark:text-red-500">'.$value.' <i class="fas fa-user-times"></i></a>';
                }

                return '<a href="/admin/user/'. $character->user->id.'">'.$value.'</a>';
            })->html(),

            Column::make('Character Level', 'character.level')->sortable(),
            Column::make('Last Logged In At', 'last_logged_in')->sortable(),
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
