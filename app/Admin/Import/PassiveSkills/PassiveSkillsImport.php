<?php

namespace App\Admin\Import\PassiveSkills;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\PassiveSkills\Sheets\PassiveSkillSheet;

class PassiveSkillsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new PassiveSkillSheet,
        ];
    }
}
