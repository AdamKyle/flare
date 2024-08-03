<?php

namespace App\Admin\Exports\Kingdoms;

use App\Admin\Exports\Kingdoms\Sheets\BuildingsSheet;
use App\Admin\Exports\Kingdoms\Sheets\BuildingUnitsSheet;
use App\Admin\Exports\Kingdoms\Sheets\UnitsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KingdomsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new BuildingsSheet;
        $sheets[] = new UnitsSheet;
        $sheets[] = new BuildingUnitsSheet;

        return $sheets;
    }
}
