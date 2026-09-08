<?php

namespace App\Admin\Races\Exports;

use App\Admin\Races\Exports\Sheets\RacesSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RacesExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Races workbook.
     */
    public function sheets(): array
    {
        return [
            new RacesSheet,
        ];
    }
}
