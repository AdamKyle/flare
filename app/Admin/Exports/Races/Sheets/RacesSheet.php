<?php

namespace App\Admin\Exports\Races\Sheets;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class RacesSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {


        return view('admin.exports.races.sheets.races', [
            'gameRaces' => GameRace::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Game Races';
    }
}
