<?php

namespace App\Admin\PassiveSkills\Imports;

use App\Admin\PassiveSkills\Imports\Sheets\PassiveSkillSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PassiveSkillsImport implements Import, WithMultipleSheets
{
    /**
     * Map the Passive Skills workbook sheets to their importers.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            0 => new PassiveSkillSheet,
        ];
    }
}
