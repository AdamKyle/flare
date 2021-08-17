<?php

namespace App\Admin\Import\Kingdoms;

use App\Admin\Import\Kingdoms\Sheets\BuildingsUnitsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Kingdoms\Sheets\BuildingsSheet;
use App\Admin\Import\Kingdoms\Sheets\UnitsSheet;

class KingdomsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new BuildingsSheet,
            1 => new UnitsSheet,
            2 => new BuildingsUnitsSheet,
        ];
    }
}
