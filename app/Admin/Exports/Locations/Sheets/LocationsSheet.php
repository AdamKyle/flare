<?php

namespace App\Admin\Exports\Locations\Sheets;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use App\Flare\Models\Location;

class LocationsSheet implements FromView, WithTitle, ShouldAutoSize {

    /**
     * @return View
     */
    public function view(): View {
        return view('admin.exports.locations.sheets.locations', [
            'locations' => Location::all(),
        ]);
    }

    /**
     * @return string
     */
    public function title(): string {
        return 'Locations';
    }
}
