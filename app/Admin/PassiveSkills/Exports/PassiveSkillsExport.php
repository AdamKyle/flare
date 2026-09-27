<?php

namespace App\Admin\PassiveSkills\Exports;

use App\Admin\PassiveSkills\Exports\Sheets\PassiveSkillSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PassiveSkillsExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * Build the Passive Skills workbook sheets.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new PassiveSkillSheet,
        ];
    }
}
