<?php

namespace App\Admin\Monsters\Exports\Sheets;

use App\Flare\Models\Monster;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonstersSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Build the export view containing every current Monster.
     *
     * @return View Monsters workbook export view.
     */
    public function view(): View
    {
        $monsters = Monster::with(['gameMap', 'questItem'])
            ->orderBy('game_map_id')
            ->orderBy('name')
            ->get();

        return view('admin.monsters.exports.sheets.monsters', [
            'monsters' => $monsters,
        ]);
    }

    /**
     * Return the Monsters workbook sheet title.
     *
     * @return string Monsters workbook sheet title.
     */
    public function title(): string
    {
        return 'Monsters';
    }
}
