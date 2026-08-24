<?php

namespace App\Game\Core\Items\Tables\Definitions;

use App\Flare\Models\ItemSkill;
use App\Flare\Tables\TableColumn;
use Illuminate\Database\Eloquent\Builder;

class ItemSkillsTableDefinition
{
    public static function builder(int $itemSkillId): Builder
    {
        return ItemSkill::where('id', $itemSkillId);
    }

    /**
     * @return TableColumn[]
     */
    public static function columns(): array
    {
        return [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: function ($row) {
                    if (! is_null(auth()->user()) && auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/item-skills/'.$row->id.'">'.e($row->name).'</a>';
                    }

                    return '<a href="/information/item-skills/skill/'.$row->id.'">'.e($row->name).'</a>';
                },
            ),
            new TableColumn(
                label: 'Description',
                field: 'description',
                searchable: true,
                html: true,
                render: fn ($row) => '<p class="md:w-full md:text-wrap">'.nl2br(e($row->description)).'</p>',
            ),
            new TableColumn(
                label: 'Max level',
                field: 'max_level',
                sortable: true,
            ),
            new TableColumn(
                label: 'Kills Needed',
                field: 'total_kills_needed',
                sortable: true,
                render: fn ($row) => number_format($row->total_kills_needed),
            ),
        ];
    }
}
