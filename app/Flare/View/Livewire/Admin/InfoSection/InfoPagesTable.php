<?php

namespace App\Flare\View\Livewire\Admin\InfoSection;

use App\Flare\Models\InfoPage;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class InfoPagesTable extends DataTableComponent {

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make('Page Name', 'page_name')->searchable()->format(function ($value, $row) {
                $infoPageId = InfoPage::where('page_name', $value)->first()->id;


                return '<a href="/admin/information-management/page/'.$infoPageId.'">'.$row->page_name . '</a>';
            })->html(),

            Column::make('Created At', 'created_at'),
            Column::make('Updated At', 'updated_at'),
        ];
    }

    public function builder(): Builder {
        return InfoPage::query();
    }
}
