<?php

namespace App\Admin\Import\Skills;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Admin\Import\Skills\Sheets\SkillsSheet;

class SkillsImport implements WithMultipleSheets
{

    public function sheets(): array
    {
        return [
            0 => new SkillsSheet(),
        ];
    }
}
