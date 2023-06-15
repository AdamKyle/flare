<?php

namespace App\Admin\Exports\Monsters\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\Monster;

class MonstersSheet implements FromView, WithTitle, ShouldAutoSize {

    private string $type;

    public function __construct(string $type) {
        $this->type = $type;
    }

    /**
     * @return View
     */
    public function view(): View {

        $monsters = collect();

        switch($this->type) {
            case 'celestial':
                $monsters = Monster::orderBy('game_map_id')
                                   ->where('is_celestial_entity', true)
                                   ->where('is_raid_monster', false)
                                   ->where('is_raid_boss', false)
                                   ->get();
                break;
            case 'raid_monster':
                $monsters = Monster::orderBy('game_map_id')
                                   ->where('is_celestial_entity', false)
                                   ->where('is_raid_monster', true)
                                   ->where('is_raid_boss', false)
                                   ->get();
                break;
            case 'raid_boss':
                $monsters = Monster::orderBy('game_map_id')
                                   ->where('is_celestial_entity', false)
                                   ->where('is_raid_monster', false)
                                   ->where('is_raid_boss', true)
                                   ->get();
                break;
            case 'monster':
            default:
                $monsters = Monster::orderBy('game_map_id')
                                   ->where('is_celestial_entity', false)
                                   ->where('is_raid_monster', false)
                                   ->where('is_raid_boss', false)
                                   ->get();

        }

        return view('admin.exports.monsters.sheets.monsters', [
            'monsters' => $monsters
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Monsters';
    }
}
