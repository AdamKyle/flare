<?php

namespace App\Admin\Skills\Imports;

use App\Admin\Skills\Imports\Sheets\SkillsSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SkillsImport implements Import, WithMultipleSheets
{
    /**
     * Map the Skills workbook sheets to their importers.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            0 => new SkillsSheet,
        ];
    }
}
