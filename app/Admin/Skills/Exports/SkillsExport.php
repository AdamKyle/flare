<?php

namespace App\Admin\Skills\Exports;

use App\Admin\Skills\Exports\Sheets\SkillsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SkillsExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * Build the Skills workbook sheets.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new SkillsSheet,
        ];
    }
}
