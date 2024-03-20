<?php

namespace App\Flare\View\Livewire\Admin\Monsters;

use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class MonstersTable extends DataTableComponent {

    public function configure(): void {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder {

        return Monster::query();
    }

    public function filters(): array {

        return [
            SelectFilter::make('Maps')
                ->options([
                    ''                            => 'Please Select',
                    'Surface'                     => 'Surface',
                    'Labyrinth'                   => 'Labyrinth',
                    'Dungeons'                    => 'Dungeons',
                    'Shadow Plane'                => 'Shadow Plane',
                    'Hell'                        => 'Hell',
                    'Purgatory'                   => 'Purgatory',
                    'The Ice Plane'               => 'The Ice Plane',
                    'Twisted Memories'            => 'Twisted Memories',
                    'Surface Celestials'          => 'Surface Celestials',
                    'Labyrinth Celestials'        => 'Labyrinth Celestials',
                    'Dungeons Celestials'         => 'Dungeons Celestials',
                    'Shadow Plane Celestials'     => 'Shadow Planes Celestials',
                    'Purgatory Celestials'        => 'Purgatory Celestials',
                    'Surface Raid Bosses'         => 'Surface Raid Bosses',
                    'Labyrinth Raid Bosses'       => 'Labyrinth Raid Bosses',
                    'Dungeons Raid Bosses'        => 'Dungeons Raid Bosses',
                    'Shadow Plane Raid Bosses'    => 'Shadow Plane Raid Bosses',
                    'Hell Raid Bosses'            => 'Hell Raid Bosses',
                    'Purgatory Raid Bosses'       => 'Purgatory Raid Bosses',
                    'Surface Raid Monsters'       => 'Surface Raid Monsters',
                    'Labyrinth Raid Monsters'     => 'Labyrinth Raid Monsters',
                    'Dungeons Raid Monsters'      => 'Dungeons Raid Monsters',
                    'Shadow Plane Raid Monsters'  => 'Shadow Plane Raid Monsters',
                    'Hell Raid Monsters'          => 'Hell Raid Monsters',
                    'Purgatory Raid Monsters'     => 'Purgatory Raid Monsters',
                    'The Ice Plane Raid Monsters' => 'The Ice Plane Raid Monsters',
                ])->filter(function (Builder $builder, string $value) {
                    if (str_contains($value, 'Celestials')) {

                        $gameMapId = GameMap::where('name', trim(str_replace('Celestials', '', $value)))->first()->id;

                        return $builder->where('game_map_id', $gameMapId)->where('is_celestial_entity', true);
                    }

                    if (str_contains($value, 'Raid Bosses')) {

                        $gameMapId = GameMap::where('name', trim(str_replace('Raid Bosses', '', $value)))->first()->id;

                        return $builder->where('game_map_id', $gameMapId)->where('is_raid_boss', true);
                    }

                    if (str_contains($value, 'Raid Monsters')) {

                        $gameMapId = GameMap::where('name', trim(str_replace('Raid Monsters', '', $value)))->first()->id;

                        return $builder->where('game_map_id', $gameMapId)->where('is_raid_monster', true);
                    }

                    $gameMapId = GameMap::where('name', $value)->first()->id;

                    return $builder->where('game_map_id', $gameMapId)
                        ->where('is_celestial_entity', false)
                        ->where('is_raid_boss', false);
                }),
        ];
    }

    public function columns(): array {
        return [
            Column::make('Name')->searchable()->format(function ($value, $row) {
                $monsterId = Monster::where('name', $value)->first()->id;

                if (!is_null(auth()->user())) {
                    if (auth()->user()->hasRole('Admin')) {
                        return '<a href="/admin/monsters/' . $monsterId . '">' . $row->name . '</a>';
                    }
                }

                return '<a href="/monsters/' . $monsterId . '" >' . $row->name . '</a>';
            })->html(),
            Column::make('Is Raid Boss', 'is_raid_boss')->searchable()->format(function ($value, $row) {

                return $value ? 'Yes' : 'No';
            })->html(),
            Column::make('Is Raid Monster', 'is_raid_monster')->searchable()->format(function ($value, $row) {

                return $value ? 'Yes' : 'No';
            })->html(),
            Column::make('Plane', 'gameMap.name')->searchable(),

            Column::make('Damage Stat', 'damage_stat')->sortable(),
            Column::make('Xp', 'xp')->sortable()->format(function ($value) {
                return number_format($value);
            }),
            Column::make('Gold Reward', 'gold')->sortable()->format(function ($value) {
                return number_format($value);
            }),
        ];
    }
}
