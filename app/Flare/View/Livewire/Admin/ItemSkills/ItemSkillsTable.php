<?php

namespace App\Flare\View\Livewire\Admin\ItemSkills;

use App\Flare\Models\ItemSkill;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ItemSkillsTable extends DataTableComponent
{
    public ?int $parentSkill = null;

    public ?int $itemSkillId = null;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {

        if (! is_null($this->parentSkill)) {
            return ItemSkill::where('parent_id', $this->parentSkill);
        }

        if (! is_null($this->itemSkillId)) {
            return ItemSkill::where('id', $this->itemSkillId);
        }

        return ItemSkill::query();
    }

    public function columns(): array
    {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $skillId = ItemSkill::where('name', $value)->first()->id;

                if (! is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/item-skills/'.$skillId.'">'.$row->name.'</a>';
                    }
                }

                return '<a href="/information/item-skills/skill/'.$skillId.'" >'.$row->name.'</a>';
            })->html(),
            Column::make('Description')->searchable()->format(function ($value) {
                return nl2br($value);
            }),

            Column::make('Max level', 'max_level')->sortable()->format(function ($value) {
                return $value;
            }),
            Column::make('Kills Needed', 'total_kills_needed')->sortable()->format(function ($value) {
                return number_format($value);
            }),
        ];
    }
}
