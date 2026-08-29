<?php

namespace App\Admin\Npcs\Imports;

use App\Admin\Npcs\Imports\Sheets\NpcsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class NpcsImport implements Import, WithMultipleSheets
{
    /**
     * Return the sheets included in the NPCs workbook.
     *
     * @return array<int, NpcsSheet> NPCs workbook sheets.
     */
    public function sheets(): array
    {
        return [
            0 => new NpcsSheet,
        ];
    }
}
