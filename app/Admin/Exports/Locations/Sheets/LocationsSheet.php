<?php

namespace App\Admin\Exports\Locations\Sheets;

use App\Flare\Models\Location;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;

class LocationsSheet implements FromView, ShouldAutoSize, WithTitle
{
    public function view(): View
    {
        return view('admin.exports.locations.sheets.locations', [
            'locations' => Location::all(),
        ]);
    }

    public function title(): string
    {
        return 'Locations';
    }
}
