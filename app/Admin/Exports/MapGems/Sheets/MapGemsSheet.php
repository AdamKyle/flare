<?php

namespace App\Admin\Exports\MapGems\Sheets;

use App\Flare\Models\GameMapGemParamters;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class MapGemsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.map-gems.sheets.map-gems', [
            'mapGems' => GameMapGemParamters::with('gameMap')->get(),
        ]);
    }

    public function title(): string
    {
        return 'Map Gems';
    }
}
