<?php

namespace App\Admin\Exports\ItemSkills;

use App\Admin\Exports\ItemSkills\Sheets\ItemSkillsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ItemSkillsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new ItemSkillsSheet;

        return $sheets;
    }
}
