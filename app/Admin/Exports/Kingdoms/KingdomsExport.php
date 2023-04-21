<?php

namespace App\Admin\Exports\Kingdoms;


use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Exports\Kingdoms\Sheets\BuildingsSheet;
use App\Admin\Exports\Kingdoms\Sheets\UnitsSheet;
use App\Admin\Exports\Kingdoms\Sheets\BuildingUnitsSheet;

class KingdomsExport implements WithMultipleSheets {

    use Exportable;

    /**
     * @return array
     */
    public function sheets(): array {
        $sheets   = [];

        $sheets[] = new BuildingsSheet;
        $sheets[] = new UnitsSheet;
        $sheets[] = new BuildingUnitsSheet;

        return $sheets;
    }
}
