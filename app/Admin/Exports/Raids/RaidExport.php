<?php

namespace App\Admin\Exports\Raids;

use App\Admin\Exports\Raids\Sheets\RaidSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RaidExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new RaidSheet;

        return $sheets;
    }
}
