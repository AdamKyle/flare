<?php

namespace App\Flare\View\Livewire\Admin\Feedback;

use App\Flare\Models\Character;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class BugsList extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return SuggestionAndBugs::where('type', FeedbackType::BUG);
    }


    public function columns(): array
    {
        return [
            Column::make('Character Name', 'character_id')->format(function ($value) {
                $character = Character::find($value);

                return '<a href="/admin/user/user/'.$character->user->id.'">'.$character->name.'</a>';
            })->html(),

            Column::make('Title')->sortable()->format(function ($value, $row) {
                $feedback = SuggestionAndBugs::where('title', $row->title)->where('character_id', $row->character_id)->first();

                return '<a href="/admin/feedback/bug/'.$feedback->id.'">'.$value.'</a>';
            })->html(),

            Column::make('Platform')->sortable()->format(function ($value) {
                return Str::title($value);
            }),

            Column::make('Created At')->sortable()->format(function ($value) {
                return $value->format('M j Y h:i A');
            }),
        ];
    }
}
