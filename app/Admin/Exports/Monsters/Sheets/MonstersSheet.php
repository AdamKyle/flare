<?php

namespace App\Admin\Exports\Monsters\Sheets;

use App\Flare\Models\Monster;
use App\Flare\Values\LocationType;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonstersSheet implements FromView, ShouldAutoSize, WithTitle
{
    private string $type;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function view(): View
    {

        switch ($this->type) {
            case 'celestials':
                $monsters = Monster::orderBy('game_map_id')
                    ->orderBy('gold_cost')
                    ->where('is_celestial_entity', true)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->whereNull('only_for_location_type')
                    ->get();
                break;
            case 'raid-monsters':
                $monsters = Monster::orderBy('game_map_id')
                    ->where('is_celestial_entity', false)
                    ->where('is_raid_monster', true)
                    ->where('is_raid_boss', false)
                    ->whereNull('only_for_location_type')
                    ->get();
                break;
            case 'raid-bosses':
                $monsters = Monster::orderBy('game_map_id')
                    ->where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', true)
                    ->whereNull('only_for_location_type')
                    ->get();
                break;
            case 'special-locations':
                $monsters = Monster::orderBy('game_map_id')
                    ->where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->whereNotNull('only_for_location_type')
                    ->whereNotIn('only_for_location_type', [
                        LocationType::LORDS_STRONG_HOLD,
                        LocationType::BROKEN_ANVIL,
                        LocationType::ALCHEMY_CHURCH,
                        LocationType::TWSITED_MAIDENS_DUNGEONS,
                    ])
                    ->get();
                break;
            case 'weekly-fights':
                $monsters = Monster::orderBy('game_map_id')
                    ->where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->whereNotNull('only_for_location_type')
                    ->whereIn('only_for_location_type', [
                        LocationType::LORDS_STRONG_HOLD,
                        LocationType::BROKEN_ANVIL,
                        LocationType::ALCHEMY_CHURCH,
                        LocationType::TWSITED_MAIDENS_DUNGEONS,
                    ])
                    ->get();
                break;
            case 'monsters':
            default:
                $monsters = Monster::orderBy('game_map_id')
                    ->where('is_celestial_entity', false)
                    ->where('is_raid_monster', false)
                    ->where('is_raid_boss', false)
                    ->whereNull('only_for_location_type')
                    ->get();
        }

        return view('admin.exports.monsters.sheets.monsters', [
            'monsters' => $monsters,
        ]);
    }

    public function title(): string
    {
        return 'Monsters';
    }
}
