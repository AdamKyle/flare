<?php

namespace App\Admin\Monsters\Exports;

use App\Admin\Monsters\Exports\Sheets\MonstersSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class MonstersExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * Return the sheets included in the Monsters workbook.
     *
     * @return array<int, MonstersSheet> Monsters workbook sheets.
     */
    public function sheets(): array
    {
        return [
            new MonstersSheet,
        ];
    }
}
