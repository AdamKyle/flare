<?php

namespace App\Info\Tables\Definitions;

use App\Flare\Models\GameMapGemParamter;
use App\Flare\Tables\TableColumn;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Builder;

class MapGemsTableDefinition
{
    /**
     * Build the base query for the Map Gems Information table.
     *
     * @return Builder Map Gem table query, with its Game Map relation eager loaded.
     */
    public static function builder(): Builder
    {
        return GameMapGemParamter::query()->with('gameMap');
    }

    /**
     * Columns for the non-admin, controller-rendered table (uses TableColumn/TableQueryBuilder).
     *
     * @return TableColumn[]
     */
    public static function columns(): array
    {
        $isAdmin = auth()->check() && auth()->user()->hasRole('Admin');

        $columns = [
            new TableColumn(
                label: 'Name',
                field: 'name',
                searchable: true,
                html: true,
                render: function ($row) {
                    $route = route('info.page.map-gems.show', ['gameMapGemParamter' => $row->getRouteKey()]);

                    return '<a href="'.$route.'">'.e($row->name).'</a>';
                },
            ),
            new TableColumn(
                label: 'Map',
                field: 'gameMap.name',
                searchable: true,
                sortable: true,
                sortUsing: function (Builder $query, string $direction) {
                    $query->join('game_maps', 'game_maps.id', '=', 'game_map_gem_paramters.game_map_id')
                        ->orderBy('game_maps.name', $direction)
                        ->select('game_map_gem_paramters.*');
                },
            ),
            new TableColumn(
                label: 'Monster Atonement',
                field: 'monster_atonement',
                render: fn ($row) => is_null($row->monster_atonement) ? 'N/A' : GemTypeValue::getNames()[$row->monster_atonement],
            ),
            new TableColumn(
                label: 'Character XP Range',
                field: 'character_xp_bonus_range',
                render: fn ($row) => $row->character_xp_bonus_range ?? 'N/A',
            ),
            new TableColumn(
                label: 'Gold Gain Range',
                field: 'gold_gain_range',
                render: fn ($row) => $row->gold_gain_range ?? 'N/A',
            ),
            new TableColumn(
                label: 'Crafting Skill Bonus Range',
                field: 'crafting_skill_bonus_range',
                render: fn ($row) => $row->crafting_skill_bonus_range ?? 'N/A',
            ),
        ];

        if ($isAdmin) {
            $columns[] = new TableColumn(
                label: 'Actions',
                html: true,
                render: fn ($row) => '<a href="'.route('admin.map-gems.index').'">Manage</a>',
            );
        }

        return $columns;
    }
}
