<?php

namespace App\Admin\Kingdoms\Exports\Sheets;

use App\Flare\Models\GameUnit;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class UnitsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Render the Units sheet.
     *
     * @return View
     */
    public function view(): View
    {
        return view('admin.exports.kingdoms.sheets.units', [
            'units' => GameUnit::all(),
        ]);
    }

    /**
     * Name the Units sheet.
     *
     * @return string
     */
    public function title(): string
    {
        return 'Units';
    }
}
