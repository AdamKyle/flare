<?php

namespace App\Admin\Locations\Exports\Sheets;

use App\Flare\Models\Location;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationsSheet implements FromView, ShouldAutoSize, WithTitle
{
    /**
     * Build the export view with all current Locations.
     *
     * @return View Locations workbook export view.
     */
    public function view(): View
    {
        return view('admin.locations.exports.sheets.locations', [
            'locations' => Location::all(),
        ]);
    }

    /**
     * Return the Locations workbook sheet title.
     *
     * @return string Locations workbook sheet title.
     */
    public function title(): string
    {
        return 'Locations';
    }
}
