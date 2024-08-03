<?php

namespace App\Admin\Exports\PassiveSkills;

use App\Admin\Exports\PassiveSkills\Sheets\PassiveSkillSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PassiveSkillsExport implements WithMultipleSheets
{
    use Exportable;

    public function sheets(): array
    {
        $sheets = [];

        $sheets[] = new PassiveSkillSheet;

        return $sheets;
    }
}
