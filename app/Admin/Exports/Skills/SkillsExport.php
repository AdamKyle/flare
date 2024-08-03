<?php

namespace App\Admin\Exports\Skills;

use App\Admin\Exports\Skills\Sheets\SkillsSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SkillsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new SkillsSheet;

        return $sheets;
    }
}
