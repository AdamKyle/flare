<?php

namespace App\Admin\Exports\Npcs;

use App\Admin\Exports\Npcs\Sheets\NpcsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NpcsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new NpcsSheet;

        return $sheets;
    }
}
