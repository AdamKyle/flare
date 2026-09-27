<?php

namespace App\Admin\Kingdoms\Exports\Sheets;

use App\Flare\Models\GameBuildingUnit;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BuildingUnitsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Render the Building Units sheet, listing each relationship by Building and Unit name.
     *
     * @return View
     */
    public function view(): View
    {
        return view('admin.exports.kingdoms.sheets.building-units', [
            'buildingUnits' => GameBuildingUnit::with(['gameBuilding', 'gameUnit'])->orderBy('id')->get(),
        ]);
    }

    /**
     * Name the Building Units sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Building Units';
    }
}
