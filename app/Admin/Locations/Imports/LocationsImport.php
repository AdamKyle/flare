<?php

namespace App\Admin\Locations\Imports;

use App\Admin\Locations\Imports\Sheets\LocationsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationsImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Locations workbook.
     *
     * @return array<int, LocationsSheet> Locations workbook sheets.
     */
    public function sheets(): array
    {
        return [
            0 => new LocationsSheet,
        ];
    }
}
