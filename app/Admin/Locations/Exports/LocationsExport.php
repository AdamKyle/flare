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
     */
    public function sheets(): array
    {
        return [
            new LocationsSheet,
        ];
    }
}
