<?php

namespace App\Admin\Exports\Kingdoms\Sheets;

use App\Flare\Models\GameBuildingUnit;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BuildingUnitsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.kingdoms.sheets.building-units', [
            'buildingUnits' => GameBuildingUnit::all(),
        ]);
    }

    public function title(): string
    {
        return 'Building Units';
    }
}
