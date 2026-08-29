<?php

namespace App\Admin\GameMaps\Exports\Sheets;

use App\Flare\Models\GameMap;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class GameMapsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Build the export view with deterministically ordered Game Maps.
     *
     * @return View Game Maps export sheet view.
     */
    public function view(): View
    {
        return view('admin.game-maps.exports.sheets.game-maps', [
            'gameMaps' => GameMap::query()
                ->with('requiredLocation')
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    /**
     * Return the Game Maps workbook sheet title.
     *
     * @return string The workbook sheet title.
     */
    public function title(): string
    {
        return 'Game Maps';
    }
}
