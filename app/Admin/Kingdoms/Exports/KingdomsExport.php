<?php

namespace App\Admin\Kingdoms\Exports;

use App\Admin\Kingdoms\Exports\Sheets\BuildingsSheet;
use App\Admin\Kingdoms\Exports\Sheets\BuildingUnitsSheet;
use App\Admin\Kingdoms\Exports\Sheets\UnitsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KingdomsExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * Build the Kingdom workbook sheets in import order.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new BuildingsSheet,
            new UnitsSheet,
            new BuildingUnitsSheet,
        ];
    }
}
