<?php

namespace App\Info\Tables\Definitions;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Tables\TableColumn;
use App\Game\Gems\Values\GemTypeValue;
use Illuminate\Database\Eloquent\Builder;

class LocationGemsTableDefinition
{
    /**
     * Build the base query for the Location Gems Information table.
     *
     * @return Builder Location Gem table query, with its Location and Map relations eager loaded.
     */
    public static function builder(): Builder
    {
        return GameLocationGemParamter::query()->with('location.map');
    }

    /**
     * Build the Location Gems Information table columns, including the Admin `Manage` action.
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
                    $route = route('info.page.location-gems.show', ['gameLocationGemParamter' => $row->getRouteKey()]);

                    return '<a href="'.$route.'">'.e($row->name).'</a>';
                },
            ),
            new TableColumn(
                label: 'Location',
                field: 'location.name',
                searchable: true,
                sortable: true,
                sortUsing: function (Builder $query, string $direction) {
                    $query->join('locations', 'locations.id', '=', 'game_location_gem_paramters.location_id')
                        ->orderBy('locations.name', $direction)
                        ->select('game_location_gem_paramters.*');
                },
            ),
            new TableColumn(
                label: 'Map',
                field: 'location.map.name',
                searchable: true,
                sortable: true,
                sortUsing: function (Builder $query, string $direction) {
                    $query->join('locations', 'locations.id', '=', 'game_location_gem_paramters.location_id')
                        ->join('game_maps', 'game_maps.id', '=', 'locations.game_map_id')
                        ->orderBy('game_maps.name', $direction)
                        ->select('game_location_gem_paramters.*');
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
                render: fn ($row) => '<a href="'.route('admin.location-gems.index').'">Manage</a>',
            );
        }

        return $columns;
    }
}
