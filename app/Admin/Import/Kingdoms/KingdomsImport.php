<?php

namespace App\Admin\Import\Kingdoms;

use App\Admin\Import\Kingdoms\Sheets\BuildingsSheet;
use App\Admin\Import\Kingdoms\Sheets\BuildingsUnitsSheet;
use App\Admin\Import\Kingdoms\Sheets\UnitsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class KingdomsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new BuildingsSheet,
            1 => new UnitsSheet,
            2 => new BuildingsUnitsSheet
        ];
    }
}
