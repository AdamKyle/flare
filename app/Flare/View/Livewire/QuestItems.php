<?php

namespace App\Flare\View\Livewire;

use App\Flare\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class QuestItems extends DataTableComponent
{

    protected string $pageName  = 'quest-items';

    protected string $tableName = 'quest-items';

    public string $only         = '';

    public function columns(): array
    {
        return [
            Column::make('Name'),
            Column::make('Description'),
        ];
    }

    public function query(): Builder
    {

        if ($this->only === 'quest-items-book') {
            $this->paginationEnabled = false;
            $this->showPerPage       = false;
            $this->showSearch        = false;

            return Item::where('type', 'quest')->where('name', 'like', '%Book%')->orWhere('name', 'like', '%Diary%');
        }

        return Item::where('type', 'quest');
    }
}
