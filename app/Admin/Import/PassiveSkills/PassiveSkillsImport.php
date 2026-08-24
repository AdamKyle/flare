<?php

namespace App\Admin\Import\PassiveSkills;

use App\Admin\Import\PassiveSkills\Sheets\PassiveSkillSheet;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class PassiveSkillsImport implements Import, WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new PassiveSkillSheet,
        ];
    }
}
