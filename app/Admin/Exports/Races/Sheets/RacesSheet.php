<?php

namespace App\Admin\Exports\Races\Sheets;

use App\Flare\Models\GameRace;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RacesSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {

        return view('admin.exports.races.sheets.races', [
            'gameRaces' => GameRace::all(),
        ]);
    }

    public function title(): string
    {
        return 'Game Races';
    }
}
