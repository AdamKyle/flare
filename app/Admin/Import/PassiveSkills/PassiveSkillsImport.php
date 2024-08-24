<?php

namespace App\Admin\Import\PassiveSkills;

use App\Admin\Import\PassiveSkills\Sheets\PassiveSkillSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PassiveSkillsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new PassiveSkillSheet,
        ];
    }
}
