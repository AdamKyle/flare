<?php

namespace App\Admin\Kingdoms\Exports\Sheets;

use App\Flare\Models\GameBuilding;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class BuildingsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Render the Buildings sheet.
     *
     * @return View
     */
    public function view(): View
    {
        return view('admin.exports.kingdoms.sheets.buildings', [
            'buildings' => GameBuilding::all(),
        ]);
    }

    /**
     * Name the Buildings sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Buildings';
    }
}
