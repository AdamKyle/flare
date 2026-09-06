<?php

namespace App\Admin\Locations\Exports;

use App\Admin\Locations\Exports\Sheets\LocationsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LocationsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Locations workbook.
     *
     * @return array<int, LocationsSheet> Locations workbook sheets.
     */
    public function sheets(): array
    {
        return [
            new LocationsSheet,
        ];
    }
}
