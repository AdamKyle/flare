<?php

namespace App\Admin\Exports\Locations;

use App\Admin\Exports\Locations\Sheets\LocationsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new LocationsSheet;

        return $sheets;
    }
}
