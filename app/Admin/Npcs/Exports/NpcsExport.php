<?php

namespace App\Admin\Npcs\Exports;

use App\Admin\Npcs\Exports\Sheets\NpcsSheet;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NpcsExport implements Export, WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the NPCs workbook.
     */
    public function sheets(): array
    {
        return [
            new NpcsSheet,
        ];
    }
}
