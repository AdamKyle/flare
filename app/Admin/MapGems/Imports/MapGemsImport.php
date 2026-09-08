<?php

namespace App\Admin\MapGems\Imports;

use App\Admin\MapGems\Imports\Sheets\MapGemsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MapGemsImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the Map Gems workbook.
     */
    public function sheets(): array
    {
        return [
            0 => new MapGemsSheet,
        ];
    }
}
