<?php

namespace App\Flare\View\Livewire\Admin\Chat;

use App\Game\Messages\Models\Message;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ChatLogs extends DataTableComponent
{
    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {
        return Message::query()->orderBy('id', 'desc');
    }

    public function columns(): array {
        return [
            Column::make('Id')->hideIf(true),
            Column::make('Character Name', 'user_id')->format(function($value, $row) {
                $user = $row->user;

                if ($user->hasRole('Admin')) {
                    return 'The Creator';
                }

                return '<a href="/admin/user/'.$user->id.'">'.$user->character->name.'</a>';
            })->html(),
            Column::make('Message'),
            Column::make('From', 'from_user')->format(function($value, $row) {
                if ($value !== null) {
                    $user = $row->fromUser;

                    if ($user->hasRole('Admin')) {
                        return 'The Creator';
                    }

                    return '<a href="/admin/user/' . $user->id . '">' . $user->character->name . '</a>';
                }

                return  'N/A';
            })->html(),
            Column::make('To', 'to_user')->format(function($value, $row) {
                if ($value !== null) {
                    $user = $row->toUser;

                    if ($user->hasRole('Admin')) {
                        return 'The Creator';
                    }

                    return '<a href="/admin/user/' . $user->id . '">' . $user->character->name . '</a>';
                }

                return  'N/A';
            })->html(),
            Column::make('Sent At', 'created_at')->format(function($value, $row) {
                return $row->created_at->format('l jS \\of F Y h:i:s A');
            })->html(),
        ];
    }
}
