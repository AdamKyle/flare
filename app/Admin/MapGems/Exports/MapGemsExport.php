<?php

namespace App\Admin\MapGems\Exports;

use App\Admin\MapGems\Exports\Sheets\MapGemsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MapGemsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Map Gems workbook.
     */
    public function sheets(): array
    {
        return [
            new MapGemsSheet,
        ];
    }
}
