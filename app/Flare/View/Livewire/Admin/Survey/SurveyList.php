<?php

namespace App\Flare\View\Livewire\Admin\Survey;

use App\Flare\Models\Survey;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SurveyList extends DataTableComponent
{
    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Survey::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Title', 'title')->format(function ($value) {
                $survey = Survey::where('title', $value)->first();

                return '<a href="/admin/view-survey/'.$survey->id.'">'.$value.'</a>';
            })->html(),

            Column::make('Created At')->sortable()->format(function ($value) {
                return $value->format('M j Y h:i A');
            }),
        ];
    }
}
