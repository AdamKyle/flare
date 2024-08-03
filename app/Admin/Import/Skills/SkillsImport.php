<?php

namespace App\Admin\Import\Skills;

use App\Admin\Import\Skills\Sheets\SkillsSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SkillsImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            0 => new SkillsSheet,
        ];
    }
}
