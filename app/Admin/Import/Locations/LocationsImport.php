<?php

namespace App\Admin\Import\Npcs;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Locations\Sheets\LocationsSheet;

class LocationsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new LocationsSheet
        ];
    }
}
