<?php

namespace App\Admin\Races\Imports;

use App\Admin\Races\Imports\Sheets\RacesSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RacesImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Races workbook.
     *
     * @return array<int, RacesSheet> Races workbook sheets.
     */
    public function sheets(): array
    {
        return [
            0 => new RacesSheet,
        ];
    }
}
