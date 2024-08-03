<?php

namespace App\Admin\Exports\Races;

use App\Admin\Exports\Races\Sheets\RacesSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RacesExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new RacesSheet;

        return $sheets;
    }
}
