<?php

namespace App\Flare\View\Livewire;

use App\Flare\Models\ItemAffix;
use App\Flare\Values\ItemAffixType;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;

class Affixes extends DataTableComponent
{
    protected string $pageName = 'affixes';

    protected string $tableName = 'affixes';

    public function columns(): array
    {

        $columns = [
            Column::make('Name')->searchable(),
            Column::make('Min Crafting Lv.', 'skill_level_required')->sortable(),
            Column::make('Trivial Crafting Lv.', 'skill_level_trivial')->sortable(),
        ];

        if (!is_null($this->getFilter('type')) && $this->getFilter('type') !== '') {
            $columns[] = (new ItemAffixType($this->getFilter('type')))->getCustomColumn();
        }

        $columns[] = Column::make('Cost')->sortable()->format(function ($value) {
            return number_format($value);
        });

        return $columns;
    }

    public function filters(): array {
        return [
            'type' => Filter::make('Type of Affix')
                ->select(array_merge(['' => 'Please Select'], ItemAffixType::$dropDownValues)),
        ];
    }

    public function query(): Builder
    {
        return ItemAffix::query()->where('randomly_generated', false)
                                 ->when($this->getFilter('type'), function($query, $type) {
                                    return (new ItemAffixType($type))->getQuery($query);
                                 });
    }
}
