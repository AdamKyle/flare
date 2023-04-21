<?php

namespace App\Flare\View\Livewire\Admin\GuideQuests;

use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Flare\Models\GuideQuest;

class QuestsTable extends DataTableComponent {

    public function builder(): Builder {
        return GuideQuest::query();
    }

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $guideQuest = GuideQuest::where('name', $value)->first()->id;

                return '<a href="/admin/guide-quests/show/'. $guideQuest.'">'.$row->name . '</a>';
            })->html(),
            Column::make('Reward Level', 'reward_level')->sortable(),
            Column::make('Actions')->label(
                fn($row, Column $column) => view('admin.guide-quests.partials.table.delete-action')->withRow($row)
            ),
        ];
    }
}
