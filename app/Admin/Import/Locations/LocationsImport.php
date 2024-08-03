<?php

namespace App\Admin\Import\Locations;

use App\Admin\Import\Locations\Sheets\LocationsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new LocationsSheet,
        ];
    }
}
