<?php

namespace App\Admin\Kingdoms\Imports;

use App\Admin\Kingdoms\Imports\Sheets\BuildingsSheet;
use App\Admin\Kingdoms\Imports\Sheets\BuildingsUnitsSheet;
use App\Admin\Kingdoms\Imports\Sheets\UnitsSheet;
use App\Admin\Kingdoms\Services\BuildingUnitAssignmentService;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KingdomsImport implements Import, WithMultipleSheets
{
    /**
     * @param BuildingUnitAssignmentService $buildingUnitAssignmentService
     */
    public function __construct(private readonly BuildingUnitAssignmentService $buildingUnitAssignmentService) {}

    /**
     * Map the Kingdom workbook sheets, in import order, sharing the Buildings sheet so relationship reconciliation covers every Building it defined.
     *
     * @return array
     */
    public function sheets(): array
    {
        $buildingsSheet = new BuildingsSheet;

        return [
            0 => $buildingsSheet,
            1 => new UnitsSheet,
            2 => new BuildingsUnitsSheet($this->buildingUnitAssignmentService, $buildingsSheet),
        ];
    }
}
